import ejs from 'ejs';
import { resolve } from 'node:path';

export const e = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
export const csrfField = token => `<input type="hidden" name="csrf_token" value="${e(token)}">`;
export async function render(view, data) {
  return ejs.renderFile(resolve(process.cwd(), 'server/views', `${view}.ejs`), {
    e, setting: (settings, key, fallback = '') => settings[key] ?? fallback,
    nl2br: value => value.replace(/\n/g, '<br>'), ...data
  });
}
export function shell(title, content, admin = false) {
  return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${e(title)} - DeCab</title><link rel="stylesheet" href="/assets/css/style.css">${admin ? '<script src="/assets/js/admin.js" defer></script>' : ''}</head><body>${admin ? adminNav() : ''}<main class="container" style="padding:32px 16px;max-width:1100px"><h1>${e(title)}</h1>${content}</main></body></html>`;
}
function adminNav() {
  const links = [['index', 'Enquiries'], ['cabs', 'Cabs'], ['services', 'Services'], ['faqs', 'FAQs'], ['sections', 'Homepage sections'], ['pages', 'Pages'], ['messages', 'Messages'], ['settings', 'Settings'], ['change-password', 'Password'], ['logout', 'Logout']];
  return `<header class="site-header"><nav class="site-nav" style="flex-wrap:wrap;padding:20px"><a href="/">DeCab website</a>${links.map(([path, label]) => `<a href="/admin/${path}.php">${label}</a>`).join('')}</nav></header>`;
}
export function field(name, value = '', { label = name.replaceAll('_', ' '), type = 'text', required = false, max = 20000, options } = {}) {
  const common = `name="${e(name)}" id="${e(name)}" ${required ? 'required' : ''}`;
  const control = options ? `<select ${common}>${options.map(v => `<option ${v === String(value) ? 'selected' : ''}>${e(v)}</option>`).join('')}</select>`
    : type === 'textarea' ? `<textarea ${common} rows="5" maxlength="${max}">${e(value)}</textarea>`
    : `<input ${common} type="${type}" ${type === 'number' ? 'step="any"' : `maxlength="${max}"`} value="${e(value)}">`;
  return `<label for="${e(name)}">${e(label)}</label>${control}`;
}
export const alert = (message, kind = 'success') => message ? `<div class="alert ${kind}">${e(message)}</div>` : '';
export function actionForm(token, fields, label, dangerous = false) {
  return `<form method="post" style="display:inline-block;margin:4px" ${dangerous ? 'data-confirm="true"' : ''}>${csrfField(token)}${Object.entries(fields).map(([key, value]) => `<input type="hidden" name="${e(key)}" value="${e(value)}">`).join('')}<button type="submit" class="btn">${e(label)}</button></form>`;
}
