import { randomBytes, createHmac, createHash, timingSafeEqual } from 'node:crypto';

export class HttpError extends Error {
  constructor(status, message) { super(message); this.status = status; }
}
export const randomToken = () => randomBytes(32).toString('hex');
export const digest = value => createHash('sha256').update(value).digest('hex');
export function secret() {
  const value = process.env.SESSION_SECRET;
  if (!value || value.length < 32 || value.startsWith('replace-')) throw new Error('Set a strong SESSION_SECRET');
  return value;
}
export const sign = value => createHmac('sha256', secret()).update(value).digest('hex');
export function equal(a, b) {
  if (typeof a !== 'string' || typeof b !== 'string') return false;
  const left = Buffer.from(a), right = Buffer.from(b);
  return left.length === right.length && timingSafeEqual(left, right);
}
export function cookies(request) {
  return Object.fromEntries((request.headers.get('cookie') || '').split(';').filter(v => v.includes('=')).map(v => {
    const i = v.indexOf('='); return [v.slice(0, i).trim(), v.slice(i + 1).trim()];
  }));
}
export function cookie(name, value, age = 28800) {
  return `${name}=${value}; Path=/; HttpOnly; SameSite=Lax; Max-Age=${age}${process.env.COOKIE_SECURE === 'false' ? '' : '; Secure'}`;
}
export function csrf(request) {
  const saved = cookies(request).decab_csrf || '';
  const parts = saved.split('.');
  const valid = parts.length === 3 && /^\d+$/.test(parts[1]) && Number(parts[1]) > Date.now() && Number(parts[1]) < Date.now() + 86401000 && equal(parts[2], sign(`${parts[0]}.${parts[1]}`));
  const payload = `${randomToken()}.${Date.now() + 86400000}`;
  const token = valid ? saved : `${payload}.${sign(payload)}`;
  return { token, header: valid ? null : cookie('decab_csrf', token, 86400), valid };
}
export function verifyCsrf(request, form, state) {
  const origin = request.headers.get('origin');
  if ((origin && origin !== new URL(request.url).origin) || !state.valid || !equal(form.csrf_token, state.token)) {
    throw new HttpError(403, 'Your form expired. Reload the page and try again.');
  }
}
export async function readForm(request) {
  if (!request.headers.get('content-type')?.startsWith('application/x-www-form-urlencoded')) throw new HttpError(415, 'Unsupported form format.');
  if (Number(request.headers.get('content-length')) > 65536) throw new HttpError(413, 'Form is too large.');
  const body = await request.text();
  if (Buffer.byteLength(body) > 65536) throw new HttpError(413, 'Form is too large.');
  return Object.fromEntries(new URLSearchParams(body));
}
export function text(value, name, max, required = false) {
  const result = String(value ?? '').trim();
  if ((required && !result) || result.length > max) throw new HttpError(400, `Please enter a valid ${name} (maximum ${max} characters).`);
  return result;
}
export function email(value, required = false) {
  const result = text(value, 'email address', 150, required);
  if (result && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(result)) throw new HttpError(400, 'Please enter a valid email address.');
  return result;
}
export function phone(value, required = false) {
  const result = text(value, 'phone number', 20, required);
  if (result && !/^[0-9+\-\s]{7,20}$/.test(result)) throw new HttpError(400, 'Please enter a valid phone number.');
  return result;
}
