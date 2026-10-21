import sanitizeHtml from './generated/sanitize.cjs';
import { getDatabase } from './db.mjs';
import { csrf, verifyCsrf, readForm, HttpError, text, email, phone, sign, equal } from './security.mjs';
import { render, e, csrfField, shell, field, alert } from './html.mjs';
import { session, login } from './auth.mjs';
import { adminPage } from './admin.mjs';

const publicRoutes = new Set(['index', 'about', 'services', 'pricing', 'faq', 'contact', 'booking-success']);
const adminRoutes = new Set(['index', 'login', 'logout', 'settings', 'pages', 'sections', 'services', 'faqs', 'cabs', 'messages', 'change-password']);
const sectionTables = ['stats', 'steps', 'features', 'testimonials'];
const securityHeaders = {
  'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store',
  'X-Content-Type-Options': 'nosniff', 'X-Frame-Options': 'SAMEORIGIN',
  'Referrer-Policy': 'strict-origin-when-cross-origin', 'Permissions-Policy': 'camera=(), microphone=(), geolocation=()'
};
export function routeFor(pathname) {
  if (pathname === '/') return { admin: false, name: 'index' };
  if (pathname === '/admin' || pathname === '/admin/') return { admin: true, name: 'index' };
  const match = /^\/(admin\/)?([a-z-]+)(?:\.php|\.html)?\/?$/.exec(pathname);
  if (!match) return null;
  const admin = Boolean(match[1]), name = match[2];
  return (admin ? adminRoutes : publicRoutes).has(name) ? { admin, name } : null;
}
async function publicData(db) {
  const queries = [
    'SELECT skey, svalue FROM settings',
    'SELECT * FROM services ORDER BY id', 'SELECT * FROM faqs ORDER BY id',
    'SELECT * FROM cabs WHERE status = ? ORDER BY id',
    'SELECT * FROM pages WHERE slug = ?',
    ...sectionTables.map(table => `SELECT * FROM ${table} ORDER BY sort, id`)
  ];
  const rows = await Promise.all(queries.map((query, i) => db.execute(query, i === 3 ? ['available'] : i === 4 ? ['about'] : []).then(([rows]) => rows)));
  return {
    settings: Object.fromEntries(rows[0].map(row => [row.skey, row.svalue])), services: rows[1], faqs: rows[2],
    vehicle: rows[3][0] || null, page: rows[4][0] || null,
    getContent: table => rows[5 + sectionTables.indexOf(table)] || []
  };
}
const receiptSignature = id => sign(`booking-receipt:${id}`);
function loginHtml(token, message = '', error = '') {
  return shell('DeCab Admin Login', `<p><a href="/">Back to Website</a></p>${alert(message)}${alert(error, 'error')}<form method="post" class="card" style="max-width:480px">${csrfField(token)}${field('username', '', { required: true, max: 50 })}${field('password', '', { type: 'password', required: true, max: 72 })}<button class="btn">Login to Dashboard</button></form>`);
}
export function createApp({ database = getDatabase, logger = console } = {}) {
  return async function app(request) {
    const headers = new Headers(securityHeaders);
    const url = new URL(request.url);
    const send = (body, status = 200) => new Response(request.method === 'HEAD' ? null : body, { status, headers });
    const redirect = (location, extraCookie) => {
      headers.set('Location', location);
      if (extraCookie) headers.append('Set-Cookie', extraCookie);
      return send('', 303);
    };
    try {
      const route = routeFor(url.pathname);
      if (!route) return send(shell('Page not found', '<p><a href="/">Return to DeCab</a></p>'), 404);
      if (!['GET', 'HEAD', 'POST'].includes(request.method)) {
        headers.set('Allow', 'GET, HEAD, POST');
        throw new HttpError(405, 'Method not allowed.');
      }
      const state = csrf(request);
      if (state.header) headers.append('Set-Cookie', state.header);
      const form = request.method === 'POST' ? await readForm(request) : {};
      if (request.method === 'POST') verifyCsrf(request, form, state);
      const db = database();
      if (route.admin) {
        if (route.name === 'login') {
          if (request.method === 'POST') {
            try { return redirect('/admin/index.php', await login(request, db, form)); }
            catch (error) {
              if (!(error instanceof HttpError)) throw error;
              return send(loginHtml(state.token, '', error.message), error.status);
            }
          }
          if (await session(request, db)) return redirect('/admin/index.php');
          return send(loginHtml(state.token, url.searchParams.get('msg')));
        }
        const admin = await session(request, db);
        if (!admin) return redirect('/admin/login.php');
        const result = await adminPage({ request, db, url, route: route.name, form, token: state.token, admin });
        return result.location ? redirect(result.location, result.cookie) : send(result.html);
      }
      if (request.method === 'POST' && !['index', 'contact'].includes(route.name)) throw new HttpError(405, 'Method not allowed.');
      const data = await publicData(db);
      let error = '', sent = url.searchParams.get('sent') === '1', booking = null, status = 200;
      if (request.method === 'POST') {
        try {
          const name = text(form.name, 'name', 100, true);
          if (route.name === 'contact') {
            await db.execute('INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)', [name, email(form.email), phone(form.phone), text(form.message, 'message', 10000, true)]);
            return redirect('/contact.php?sent=1');
          }
          const customerEmail = email(form.email, true), customerPhone = phone(form.phone, true);
          const pickup = text(form.pickup, 'pickup location', 255, true), notes = text(form.notes, 'extra details', 10000);
          // Availability is checked again in the same statement that writes the enquiry.
          const [result] = await db.execute("INSERT INTO bookings (cab_id, customer_name, customer_email, customer_phone, pickup, drop_location, ride_date, ride_time, distance_km, fare, trip_type, passenger_count, notes) SELECT id, ?, ?, ?, ?, '', NULL, NULL, 0, 0, 'General enquiry', 1, ? FROM cabs WHERE status = 'available' ORDER BY id LIMIT 1", [name, customerEmail, customerPhone, pickup, notes]);
          if (!result.affectedRows) throw new HttpError(409, 'No vehicle is available right now. Please call us directly.');
          return redirect(`/booking-success.php?id=${result.insertId}&receipt=${receiptSignature(result.insertId)}`);
        } catch (err) {
          if (!(err instanceof HttpError)) throw err;
          error = err.message; status = err.status;
        }
      }
      if (route.name === 'booking-success') {
        const id = url.searchParams.get('id') || '';
        if (!/^[1-9]\d*$/.test(id) || !equal(url.searchParams.get('receipt'), receiptSignature(id))) throw new HttpError(404, 'Enquiry not found.');
        const [rows] = await db.execute('SELECT id, customer_name, customer_email, customer_phone, pickup, notes FROM bookings WHERE id = ?', [id]);
        booking = rows[0];
        if (!booking) throw new HttpError(404, 'Enquiry not found.');
      }
      const titles = { index: 'Cab Ride Enquiry', about: 'About Us', services: 'Our Services', pricing: 'Fare Enquiry', faq: 'FAQ', contact: 'Contact Us', 'booking-success': 'Enquiry Received' };
      return send(await render(route.name, {
        ...data, _POST: form, error, sent, booking, pageTitle: titles[route.name],
        csrfField: () => csrfField(state.token), safeHtml: value => sanitizeHtml(value || '')
      }), status);
    } catch (error) {
      const known = error instanceof HttpError;
      if (!known) logger.error('DeCab request failed:', error.code || error.message);
      return send(shell(known ? 'Unable to complete request' : 'Service temporarily unavailable', `<div class="alert error">${e(known ? error.message : 'Please try again later or contact us by phone.')}</div><p><a href="/">Return to DeCab</a></p>`), known ? error.status : 503);
    }
  };
}
