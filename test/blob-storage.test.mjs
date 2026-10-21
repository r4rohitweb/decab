import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import bcrypt from 'bcryptjs';
import { sqliteEngine, SqliteConnection, withBlobDatabase } from '../server/blob-db.mjs';
import { createApp } from '../server/app.mjs';

process.env.SESSION_SECRET = 'blob-test-secret-more-than-32-characters';
process.env.COOKIE_SECURE = 'false';
async function fixture() {
  const SQL = await sqliteEngine();
  const database = new SQL.Database();
  database.run(await readFile('sql/netlify-sqlite.sql', 'utf8'));
  const db = new SqliteConnection(database);
  await db.execute('INSERT INTO admins (username, password) VALUES (?, ?)', ['admin', await bcrypt.hash('Test password 123!', 10)]);
  await db.execute('INSERT INTO cabs (name) VALUES (?)', ['Test Cab']);
  await db.execute('INSERT INTO settings (skey, svalue) VALUES (?, ?)', ['site_name', 'DeCab']);
  const store = {
    bytes: database.export(), version: 1, conflicts: 0,
    async getWithMetadata() { return { data: this.bytes.slice().buffer, etag: String(this.version) }; },
    async set(key, bytes, options) {
      if (options.onlyIfMatch !== String(this.version)) { this.conflicts++; return { modified: false }; }
      this.bytes = new Uint8Array(bytes).slice(); this.version++;
      return { modified: true, etag: String(this.version) };
    }
  };
  database.close();
  return store;
}
const count = async (store, table) => {
  let result;
  await withBlobDatabase(async db => { [[result]] = await db.execute(`SELECT COUNT(*) AS total FROM ${table}`); return new Response('ok'); }, store);
  return result.total;
};
function client(store) {
  const jar = new Map();
  return async (path, values) => {
    const headers = { cookie: [...jar].map(([k, v]) => `${k}=${v}`).join('; ') };
    if (values) headers['content-type'] = 'application/x-www-form-urlencoded';
    const request = new Request('http://localhost:8888' + path, { method: values ? 'POST' : 'GET', headers, ...(values ? { body: new URLSearchParams({ csrf_token: jar.get('decab_csrf'), ...values }) } : {}) });
    const response = await withBlobDatabase(db => createApp({ database: () => db })(request.clone()), store);
    for (const value of response.headers.getSetCookie()) { const [pair] = value.split(';'); const index = pair.indexOf('='); jar.set(pair.slice(0, index), pair.slice(index + 1)); }
    return response;
  };
}
test('persistent SQLite handles pages, contact, authentication, settings, and logout', async () => {
  const store = await fixture(), request = client(store);
  for (const path of ['/', '/about.php', '/services.php', '/pricing.php', '/faq.php', '/contact.php']) assert.equal((await request(path)).status, 200, path);
  assert.equal((await request('/contact.php', { name: 'Customer', message: 'Hello' })).status, 303);
  assert.equal(await count(store, 'contact_messages'), 1);
  assert.equal((await request('/admin/login.php', { username: 'admin', password: 'wrong' })).status, 401);
  assert.equal((await request('/admin/login.php', { username: 'admin', password: 'Test password 123!' })).status, 303);
  for (const page of ['index', 'messages', 'settings', 'pages', 'sections', 'cabs', 'faqs', 'services', 'change-password']) assert.equal((await request(`/admin/${page}.php`)).status, 200, page);
  const spec = JSON.parse(await readFile('server/settings.json', 'utf8'));
  const values = Object.fromEntries(Object.entries(spec).map(([key, cfg]) => [key, cfg.default]));
  values.site_name = 'Persistent DeCab';
  assert.equal((await request('/admin/settings.php', values)).status, 303);
  assert.match(await (await request('/')).text(), /Persistent DeCab/);
  assert.equal((await request('/admin/sections.php?tab=stats', { action: 'add', label: 'Saved', svalue: '100', sort: '1' })).status, 303);
  assert.equal(await count(store, 'stats'), 1);
  assert.equal((await request('/admin/logout.php', {})).status, 303);
  assert.equal((await request('/admin/index.php')).status, 303);
  assert.equal(await count(store, 'admin_sessions'), 0);
});
test('concurrent booking writes are retried without losing enquiries or duplicating receipts', async () => {
  const store = await fixture();
  const clients = await Promise.all(Array.from({ length: 5 }, async () => { const request = client(store); await request('/'); return request; }));
  const responses = await Promise.all(clients.map((request, i) => request('/', { name: `Customer ${i}`, email: `customer${i}@example.com`, phone: '9876543210', pickup: 'Station' })));
  for (const response of responses) assert.equal(response.status, 303);
  assert.equal(await count(store, 'bookings'), 5);
  assert.equal(new Set(responses.map(r => r.headers.get('location'))).size, 5);
  assert.ok(store.conflicts > 0);
  for (let i = 0; i < responses.length; i++) assert.equal((await clients[i](responses[i].headers.get('location'))).status, 200);
});
test('failed persistence never returns a successful booking response', async () => {
  const store = await fixture(), request = client(store);
  await request('/');
  store.set = async () => ({ modified: false });
  const response = await request('/', { name: 'Customer', email: 'test@example.com', phone: '9876543210', pickup: 'Station' });
  assert.equal(response.status, 503);
  assert.equal(await count(store, 'bookings'), 0);
});
