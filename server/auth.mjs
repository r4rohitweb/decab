import bcrypt from 'bcryptjs';
import { cookie, cookies, digest, randomToken, sign, HttpError, text } from './security.mjs';

// A valid hash keeps invalid usernames on the same expensive password-check path.
const dummyHash = bcrypt.hashSync('unusable-password-' + randomToken(), 10);
export const verifyPassword = (password, hash) => bcrypt.compare(password, hash.replace(/^\$2y\$/, '$2b$'));
export async function session(request, db) {
  const token = cookies(request).decab_session;
  if (!/^[a-f0-9]{64}$/.test(token || '')) return null;
  const [rows] = await db.execute('SELECT a.id, a.username FROM admin_sessions s JOIN admins a ON a.id = s.admin_id WHERE s.token_hash = ? AND s.expires_at > UTC_TIMESTAMP()', [digest(token)]);
  return rows[0] || null;
}
export async function login(request, db, form) {
  const username = text(form.username, 'username', 50, true);
  const password = String(form.password || '');
  if (!password || Buffer.byteLength(password) > 72) throw new HttpError(401, 'Invalid username or password.');
  // Netlify overwrites this header. The local adapter uses a single local bucket.
  const ip = request.headers.get('x-nf-client-connection-ip') || 'local';
  const key = sign(`login:${ip}`);
  await db.execute('INSERT INTO login_attempts (attempt_key, attempts, expires_at) VALUES (?, 1, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE attempts = IF(expires_at <= UTC_TIMESTAMP(), 1, attempts + 1), expires_at = IF(expires_at <= UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 MINUTE), expires_at)', [key]);
  const [attempts] = await db.execute('SELECT attempts FROM login_attempts WHERE attempt_key = ?', [key]);
  if (attempts[0].attempts > 10) throw new HttpError(429, 'Too many login attempts. Please try again in 15 minutes.');
  const [rows] = await db.execute('SELECT id, username, password FROM admins WHERE username = ?', [username]);
  const admin = rows[0];
  const valid = await verifyPassword(password, admin?.password || dummyHash);
  if (!admin || !valid) throw new HttpError(401, 'Invalid username or password.');
  const token = randomToken();
  await db.execute('DELETE FROM admin_sessions WHERE expires_at <= UTC_TIMESTAMP()');
  await db.execute('DELETE FROM login_attempts WHERE attempt_key = ? OR expires_at <= UTC_TIMESTAMP()', [key]);
  const previous = cookies(request).decab_session;
  if (previous) await db.execute('DELETE FROM admin_sessions WHERE token_hash = ?', [digest(previous)]);
  await db.execute('INSERT INTO admin_sessions (token_hash, admin_id, expires_at) VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 8 HOUR))', [digest(token), admin.id]);
  return cookie('decab_session', token);
}
export async function logout(request, db) {
  const token = cookies(request).decab_session;
  if (token) await db.execute('DELETE FROM admin_sessions WHERE token_hash = ?', [digest(token)]);
  return cookie('decab_session', '', 0);
}
export async function changePassword(db, admin, form) {
  const [rows] = await db.execute('SELECT password FROM admins WHERE id = ?', [admin.id]);
  if (!rows[0] || !await verifyPassword(String(form.current_password || ''), rows[0].password)) throw new HttpError(400, 'Current password is incorrect.');
  const password = String(form.new_password || '');
  if (password.length < 8 || Buffer.byteLength(password) > 72 || password !== form.confirm_password) throw new HttpError(400, 'Use 8–72 bytes for the new password and enter the same confirmation.');
  await db.execute('UPDATE admins SET password = ? WHERE id = ?', [await bcrypt.hash(password, 12), admin.id]);
  await db.execute('DELETE FROM admin_sessions WHERE admin_id = ?', [admin.id]);
}
