import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import mysql from 'mysql2/promise';
import bcrypt from 'bcryptjs';
import { randomBytes } from 'node:crypto';
import { createApp } from '../server/app.mjs';

// Opt in explicitly. The suite creates and removes ONLY its own randomly named DB.
test('MySQL integration: pages, forms, receipts, authentication, admin CRUD', { skip: process.env.TEST_MYSQL !== 'true' }, async t => {
  process.env.SESSION_SECRET = 'integration-test-secret-more-than-32-characters';
  process.env.COOKIE_SECURE = 'false';
  const config = { host: process.env.TEST_DB_HOST || '127.0.0.1', port: Number(process.env.TEST_DB_PORT || 3306), user: process.env.TEST_DB_USER || 'root', password: process.env.TEST_DB_PASS || '' };
  const name = `decab_netlify_test_${randomBytes(8).toString('hex')}`;
  const root = await mysql.createConnection({ ...config, multipleStatements: true });
  let db;
  try {
    await root.query(`CREATE DATABASE ${name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`);
    await root.query(`USE ${name}`);
    const schema = (await readFile('sql/schema.sql', 'utf8')).replace(/^CREATE DATABASE[^;]*;\s*/m, '').replace(/^USE [^;]*;\s*/m, '');
    await root.query(schema);
    await root.query(await readFile('sql/netlify-runtime.sql', 'utf8'));
    await root.execute('UPDATE admins SET password = ? WHERE username = ?', [(await bcrypt.hash('Test password 123!', 10)).replace(/^\$2b\$/, '$2y$'), 'admin']);
    db = mysql.createPool({ ...config, database: name, connectionLimit: 3 });
    const app = createApp({ database: () => db });
    const jar = new Map();
    let csrf;
    async function request(path, values, extra = {}) {
      const headers = { cookie: [...jar].map(([k, v]) => `${k}=${v}`).join('; '), ...extra };
      if (values) headers['content-type'] = 'application/x-www-form-urlencoded';
      const response = await app(new Request('http://localhost:8888' + path, { method: values ? 'POST' : 'GET', headers, ...(values ? { body: new URLSearchParams({ csrf_token: csrf, ...values }) } : {}) }));
      for (const cookie of response.headers.getSetCookie()) {
        const pair = cookie.split(';')[0], at = pair.indexOf('=');
        jar.set(pair.slice(0, at), pair.slice(at + 1));
      }
      csrf = jar.get('decab_csrf');
      return response;
    }
    let bookingId, receipt;
    await t.test('all public pages render and escape stored content', async () => {
      for (const path of ['/', '/index.php', '/about.php', '/services.php', '/pricing.php', '/faq.php', '/contact.php']) {
        const response = await request(path);
        assert.equal(response.status, 200, `${path}: ${await response.clone().text()}`);
        assert.match(await response.text(), /DeCab/);
      }
      const denied = await request('/admin/index.php');
      assert.equal(denied.headers.get('location'), '/admin/login.php');
      await root.query("UPDATE pages SET content = '<p>Hello</p><script>alert(1)</script>' WHERE slug = 'about'");
      const html = await (await request('/about.php')).text();
      assert.match(html, /<p>Hello<\/p>/);
      assert.doesNotMatch(html, /<script>/);
    });
    await t.test('CSRF and validation prevent invalid submissions', async () => {
      assert.equal((await request('/', { csrf_token: 'forged' })).status, 403);
      assert.equal((await request('/', { name: 'Test', email: 'bad' })).status, 400);
      assert.equal((await request('/', { name: 'Test' }, { origin: 'https://foreign.test' })).status, 403);
      const [[row]] = await db.query('SELECT COUNT(*) AS total FROM bookings');
      assert.equal(row.total, 0);
    });
    await t.test('booking persists and receipt cannot be enumerated', async () => {
      const response = await request('/', { name: '<script>Test</script>', email: 'test@example.com', phone: '9876543210', pickup: 'Station', notes: 'Airport tomorrow' });
      assert.equal(response.status, 303);
      receipt = response.headers.get('location');
      bookingId = new URL(receipt, 'http://localhost').searchParams.get('id');
      const result = await request(receipt);
      assert.equal(result.status, 200);
      const html = await result.text();
      assert.match(html, /&lt;script&gt;Test/);
      assert.doesNotMatch(html, /<script>Test/);
      assert.equal((await request(`/booking-success.php?id=${bookingId}`)).status, 404);
    });
    await t.test('contact submission persists and uses redirect after POST', async () => {
      const response = await request('/contact.php', { name: 'Customer', email: 'hello@example.com', phone: '', message: 'Question about airport rides' });
      assert.equal(response.headers.get('location'), '/contact.php?sent=1');
      const [[row]] = await db.query('SELECT COUNT(*) AS total FROM contact_messages');
      assert.equal(row.total, 1);
    });
    await t.test('PHP bcrypt accounts sign in and every admin screen renders', async () => {
      const response = await request('/admin/login.php', { username: 'admin', password: 'Test password 123!' });
      assert.equal(response.status, 303);
      assert.ok(jar.get('decab_session'));
      for (const page of ['index', 'cabs', 'services', 'faqs', 'sections', 'pages', 'messages', 'settings', 'change-password', 'logout']) {
        const result = await request(`/admin/${page}.php`);
        assert.equal(result.status, 200, `${page}: ${await result.clone().text()}`);
      }
    });
    await t.test('admin updates bookings, messages, sections, settings and content', async () => {
      assert.equal((await request('/admin/index.php', { action: 'confirmed', id: bookingId })).status, 303);
      assert.equal((await request('/admin/messages.php', { action: 'read', id: '1' })).status, 303);
      assert.equal((await request('/admin/sections.php?tab=stats', { action: 'add', svalue: '100', label: 'Test', sort: '9' })).status, 303);
      assert.equal((await request('/admin/pages.php', { action: 'add', slug: 'test', title: 'Test', content: '<p>New page</p>' })).status, 303);
      assert.equal((await request('/admin/faqs.php', { action: 'add', question: 'Test?', answer: 'Yes' })).status, 303);
      const [[faq]] = await db.query('SELECT MAX(id) AS id FROM faqs');
      assert.equal((await request('/admin/faqs.php', { action: 'update', id: faq.id, question: 'Updated?', answer: 'Updated' })).status, 303);
      assert.equal((await request('/admin/faqs.php', { action: 'delete', id: faq.id })).status, 303);
      assert.equal((await request('/admin/sections.php?tab=admins', { action: 'delete', id: '1' })).status, 404);
      assert.equal((await request('/admin/cabs.php', { action: 'delete', id: '1' })).status, 409);
      const [[row]] = await db.query('SELECT status FROM bookings WHERE id = ?', [bookingId]);
      assert.equal(row.status, 'confirmed');
    });
    await t.test('logout revokes server session', async () => {
      const old = jar.get('decab_session');
      assert.equal((await request('/admin/logout.php', {})).status, 303);
      jar.set('decab_session', old);
      assert.equal((await request('/admin/index.php')).headers.get('location'), '/admin/login.php');
    });
    await t.test('settings persist, password change invalidates sessions, and unavailable cabs reject bookings', async () => {
      await request('/admin/login.php', { username: 'admin', password: 'Test password 123!' });
      const spec = JSON.parse(await readFile('server/settings.json', 'utf8'));
      const settings = Object.fromEntries(Object.entries(spec).map(([key, cfg]) => [key, cfg.default]));
      settings.site_name = 'Updated DeCab';
      assert.equal((await request('/admin/settings.php', settings)).status, 303);
      assert.match(await (await request('/')).text(), /Updated DeCab/);
      const oldSession = jar.get('decab_session');
      assert.equal((await request('/admin/change-password.php', { current_password: 'Test password 123!', new_password: 'New password 456!', confirm_password: 'New password 456!' })).status, 303);
      jar.set('decab_session', oldSession);
      assert.equal((await request('/admin/index.php')).headers.get('location'), '/admin/login.php');
      assert.equal((await request('/admin/login.php', { username: 'admin', password: 'New password 456!' })).status, 303);
      await root.query("UPDATE cabs SET status = 'busy'");
      assert.equal((await request('/', { name: 'Customer', email: 'test@example.com', phone: '9876543210', pickup: 'Station' })).status, 409);
      const [[row]] = await db.query('SELECT COUNT(*) AS total FROM bookings');
      assert.equal(row.total, 1);
    });
    await t.test('login attempts are limited', async () => {
      for (let i = 0; i < 10; i++) assert.equal((await request('/admin/login.php', { username: 'admin', password: 'wrong' })).status, 401);
      assert.equal((await request('/admin/login.php', { username: 'admin', password: 'wrong' })).status, 429);
    });
  } finally {
    if (db) await db.end();
    assert.match(name, /^decab_netlify_test_[a-f0-9]{16}$/);
    await root.query(`DROP DATABASE IF EXISTS ${name}`);
    await root.end();
  }
});
