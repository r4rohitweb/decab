import { test } from 'node:test';
import assert from 'node:assert/strict';
import { databaseOptions } from '../server/db.mjs';
import { csrf, verifyCsrf, email, phone, equal } from '../server/security.mjs';
import { createApp, routeFor } from '../server/app.mjs';

process.env.SESSION_SECRET = 'test-only-secret-with-more-than-32-characters';
test('database uses verified TLS and rejects missing configuration', () => {
  assert.throws(() => databaseOptions({}), /DB_HOST/);
  const config = { DB_HOST: 'db.example.test', DB_NAME: 'decab', DB_USER: 'app', DB_PASS: 'secret' };
  assert.equal(databaseOptions(config).ssl.rejectUnauthorized, true);
  assert.equal(databaseOptions({ ...config, DB_SSL: 'false' }).ssl, undefined);
});
test('CSRF rejects a forged cookie, missing token, and foreign origin', () => {
  const first = csrf(new Request('https://decab.test/'));
  const cookie = `decab_csrf=${first.token}`;
  const request = new Request('https://decab.test/', { headers: { cookie } });
  const state = csrf(request);
  assert.equal(state.valid, true);
  verifyCsrf(request, { csrf_token: state.token }, state);
  assert.throws(() => verifyCsrf(request, {}, state));
  const foreign = new Request('https://decab.test/', { headers: { cookie, origin: 'https://other.test' } });
  assert.throws(() => verifyCsrf(foreign, { csrf_token: state.token }, state));
  assert.equal(csrf(new Request('https://decab.test/', { headers: { cookie: cookie + 'forged' } })).valid, false);
});
test('legacy routes work and source paths are never routed', () => {
  assert.deepEqual(routeFor('/index.php'), routeFor('/'));
  assert.deepEqual(routeFor('/admin/'), { admin: true, name: 'index' });
  for (const path of ['/includes/config.php', '/sql/schema.sql', '/server/settings.json', '/.env']) assert.equal(routeFor(path), null);
});
test('invalid input is rejected before a write', () => {
  assert.equal(equal('a', 'é'), false);
  assert.throws(() => email('broken', true));
  assert.throws(() => phone('abcd', true));
  assert.equal(phone('+91 9876543210', true), '+91 9876543210');
});
test('database failures return a safe 503; unknown paths do not touch the database', async () => {
  const app = createApp({ database: () => { throw new Error('secret password'); }, logger: { error() {} } });
  const failure = await app(new Request('https://decab.test/'));
  assert.equal(failure.status, 503);
  assert.doesNotMatch(await failure.text(), /secret password/);
  const unknown = await app(new Request('https://decab.test/sql/schema.sql'));
  assert.equal(unknown.status, 404);
});
