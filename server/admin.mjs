import settingsSpec from './settings.json' with { type: 'json' };
import { e, shell, field, alert, actionForm, csrfField } from './html.mjs';
import { HttpError, text } from './security.mjs';
import { changePassword, logout } from './auth.mjs';

// All SQL identifiers below come from this fixed allowlist.
const specs = {
  cabs: { name: ['text', 100, true], model: ['text', 100], plate_number: ['text', 30], driver_name: ['text', 100], driver_phone: ['text', 30], seats: ['number', 100, true, 1], fare_per_km: ['number', 99999999], base_fare: ['number', 99999999], image: ['text', 255], status: ['select', ['available', 'busy']] },
  services: { icon: ['text', 10], title: ['text', 100, true], description: ['textarea', 20000] },
  faqs: { question: ['text', 255, true], answer: ['textarea', 20000] },
  stats: { svalue: ['text', 50, true], label: ['text', 100, true], sort: ['number', 100000] },
  steps: { step_no: ['number', 1000, true, 1], title: ['text', 100, true], description: ['textarea', 20000], sort: ['number', 100000] },
  features: { icon: ['text', 10], title: ['text', 100, true], description: ['textarea', 20000], sort: ['number', 100000] },
  testimonials: { name: ['text', 100, true], city: ['text', 100], rating: ['number', 5, true, 1], review: ['textarea', 20000], sort: ['number', 100000] },
  pages: { slug: ['text', 50, true], title: ['text', 150, true], content: ['textarea', 20000] }
};
const titles = { cabs: 'Manage Cabs', services: 'Services', faqs: 'FAQs', stats: 'Homepage Stats', steps: 'How It Works', features: 'Why Choose Us', testimonials: 'Testimonials', pages: 'Page Content' };
const sectionNames = ['stats', 'steps', 'features', 'testimonials'];
const idValue = value => {
  if (!/^[1-9]\d*$/.test(String(value)) || !Number.isSafeInteger(Number(value))) throw new HttpError(400, 'Invalid record ID.');
  return Number(value);
};
const tableHtml = (headings, rows) => `<div style="overflow-x:auto"><table class="admin-table"><thead><tr>${headings.map(h => `<th>${e(h)}</th>`).join('')}</tr></thead><tbody>${rows.length ? rows.map(row => `<tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>`).join('') : `<tr><td colspan="${headings.length}">No records yet.</td></tr>`}</tbody></table></div>`;

export async function adminPage({ request, db, url, route, form, token, admin }) {
  const post = request.method === 'POST';
  const redirect = location => ({ location });
  const page = (title, body) => ({ html: shell(title, alert(url.searchParams.get('msg')) + body, true) });
  if (route === 'logout') {
    if (post) return { location: '/admin/login.php', cookie: await logout(request, db) };
    return page('Log out', actionForm(token, {}, 'Log out'));
  }
  if (route === 'change-password') {
    if (post) {
      await changePassword(db, admin, form);
      return { location: '/admin/login.php?msg=Password+changed.+Please+sign+in+again.', cookie: await logout(request, db) };
    }
    return page('Change Password', `<form method="post" class="card">${csrfField(token)}${['current_password', 'new_password', 'confirm_password'].map(name => field(name, '', { type: 'password', required: true, max: 72 })).join('')}<button class="btn">Change Password</button></form>`);
  }
  if (route === 'settings') {
    if (post) {
      const values = Object.keys(settingsSpec).map(key => [key, text(form[key], key, 20000)]);
      const connection = await db.getConnection();
      try {
        await connection.beginTransaction();
        for (const [key, value] of values) await connection.execute('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', [key, value]);
        await connection.commit();
      } catch (error) { await connection.rollback(); throw error; } finally { connection.release(); }
      return redirect('/admin/settings.php?msg=Settings+saved');
    }
    const [rows] = await db.execute('SELECT skey, svalue FROM settings');
    const current = Object.fromEntries(rows.map(row => [row.skey, row.svalue]));
    return page('Website Settings', `<form method="post" class="card">${csrfField(token)}${Object.entries(settingsSpec).map(([key, cfg]) => field(key, current[key] ?? cfg.default, { label: `${cfg.label} (${key})`, type: cfg.type })).join('')}<button class="btn">Save Settings</button></form>`);
  }
  if (route === 'messages') {
    if (post) {
      const id = idValue(form.id);
      if (form.action === 'delete') await db.execute('DELETE FROM contact_messages WHERE id = ?', [id]);
      else if (form.action === 'read') await db.execute('UPDATE contact_messages SET is_read = 1 WHERE id = ?', [id]);
      else throw new HttpError(400, 'Invalid action.');
      return redirect('/admin/messages.php?msg=Message+updated');
    }
    const [rows] = await db.execute('SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC LIMIT 500');
    return page('Contact Messages', '<p>Showing the latest 500 messages.</p>' + tableHtml(['Customer', 'Message', 'Received', 'Actions'], rows.map(row => [
      `${e(row.name)}<br>${e(row.email)}<br>${e(row.phone)}`, e(row.message), `${e(row.created_at)}<br>${row.is_read ? 'Read' : 'Unread'}`,
      actionForm(token, { action: 'read', id: row.id }, 'Mark read') + actionForm(token, { action: 'delete', id: row.id }, 'Delete', true)
    ])));
  }
  if (route === 'index') {
    if (post) {
      const id = idValue(form.id);
      if (form.action === 'delete') await db.execute('DELETE FROM bookings WHERE id = ?', [id]);
      else {
        const allowed = { confirmed: 'pending', completed: 'confirmed', cancelled: null };
        if (!Object.hasOwn(allowed, form.action)) throw new HttpError(400, 'Invalid booking action.');
        await db.execute('UPDATE bookings SET status = ? WHERE id = ? AND status IN (?, ?)', [form.action, id, allowed[form.action] || 'pending', allowed[form.action] || 'confirmed']);
      }
      return redirect('/admin/index.php?msg=Enquiry+updated');
    }
    const [counts] = await db.execute('SELECT status, COUNT(*) AS total FROM bookings GROUP BY status');
    const [rows] = await db.execute('SELECT * FROM bookings ORDER BY created_at DESC, id DESC LIMIT 500');
    const stats = `<div class="stats">${counts.map(row => `<div class="stat-card"><h3>${e(row.total)}</h3><p>${e(row.status)}</p></div>`).join('')}</div>`;
    return page('Ride Enquiries', stats + '<p>Showing the latest 500 enquiries.</p>' + tableHtml(['ID', 'Customer', 'Pickup & Details', 'Status', 'Actions'], rows.map(row => [
      `#${e(row.id)}`, `${e(row.customer_name)}<br>${e(row.customer_email)}<br>${e(row.customer_phone)}`, `${e(row.pickup)}<br>${e(row.notes)}`, e(row.status),
      (row.status === 'pending' ? actionForm(token, { action: 'confirmed', id: row.id }, 'Confirm') : '') +
      (row.status === 'confirmed' ? actionForm(token, { action: 'completed', id: row.id }, 'Complete') : '') +
      (['pending', 'confirmed'].includes(row.status) ? actionForm(token, { action: 'cancelled', id: row.id }, 'Cancel') : '') + actionForm(token, { action: 'delete', id: row.id }, 'Delete', true)
    ])));
  }
  const isSection = route === 'sections';
  const table = isSection ? (url.searchParams.get('tab') || 'stats') : route;
  if ((isSection && !sectionNames.includes(table)) || !Object.hasOwn(specs, table)) throw new HttpError(404, 'Page not found.');
  const spec = specs[table];
  const primary = table === 'pages' ? 'slug' : 'id';
  const base = `/admin/${route}.php${isSection ? `?tab=${table}` : ''}`;
  const queryJoin = isSection ? '&' : '?';
  if (post) {
    const key = primary === 'id' ? (form.id ? idValue(form.id) : null) : text(form.original_slug, 'page slug', 50);
    if (form.action === 'delete') {
      if (!key) throw new HttpError(400, 'Missing record ID.');
      // Cab deletion cascades bookings in the original schema; keep enquiry history.
      if (table === 'cabs') {
        const [used] = await db.execute('SELECT id FROM bookings WHERE cab_id = ? LIMIT 1', [key]);
        if (used.length) throw new HttpError(409, 'This cab has enquiries. Set its status to busy instead of deleting it.');
      }
      await db.execute(`DELETE FROM ${table} WHERE ${primary} = ?`, [key]);
    } else if (['add', 'update'].includes(form.action)) {
      const values = Object.entries(spec).map(([name, [type, max, required, min = 0]]) => {
        if (type === 'select') {
          if (!max.includes(form[name])) throw new HttpError(400, `Invalid ${name}.`);
          return form[name];
        }
        if (type === 'number') {
          const value = Number(form[name]);
          if (!Number.isFinite(value) || value < min || value > max || (['seats', 'step_no', 'sort', 'rating'].includes(name) && !Number.isInteger(value))) throw new HttpError(400, `Invalid ${name}.`);
          return value;
        }
        const value = text(form[name], name, max, required);
        if (name === 'slug' && !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value)) throw new HttpError(400, 'Use lowercase letters, numbers and hyphens in the slug.');
        return value;
      });
      const columns = Object.keys(spec);
      if (form.action === 'update') {
        if (!key) throw new HttpError(400, 'Missing record ID.');
        await db.execute(`UPDATE ${table} SET ${columns.map(col => `${col} = ?`).join(', ')} WHERE ${primary} = ?`, [...values, key]);
      } else await db.execute(`INSERT INTO ${table} (${columns.join(', ')}) VALUES (${columns.map(() => '?').join(', ')})`, values);
    } else throw new HttpError(400, 'Invalid action.');
    return redirect(base + queryJoin + 'msg=Saved+successfully');
  }
  const editKey = url.searchParams.get('edit') || (table === 'pages' ? url.searchParams.get('slug') : null);
  const [items] = await db.execute(`SELECT * FROM ${table} ORDER BY ${'sort' in spec ? 'sort, ' : ''}${primary} LIMIT 500`);
  const item = items.find(row => String(row[primary]) === editKey);
  const nav = isSection ? `<p>${sectionNames.map(name => `<a class="btn" href="/admin/sections.php?tab=${name}">${e(titles[name])}</a>`).join(' ')}</p>` : '';
  const editor = `<div class="card"><h2>${item ? 'Edit' : 'Add'} ${e(titles[table])}</h2><form method="post">${csrfField(token)}<input type="hidden" name="action" value="${item ? 'update' : 'add'}"><input type="hidden" name="${primary === 'slug' ? 'original_slug' : 'id'}" value="${e(item?.[primary] || '')}">${Object.entries(spec).map(([name, [type, max, required]]) => field(name, item?.[name] ?? (type === 'number' ? (name === 'rating' ? 5 : name === 'seats' ? 4 : name === 'step_no' ? 1 : 0) : ''), { type: type === 'select' ? 'text' : type, options: type === 'select' ? max : null, max: typeof max === 'number' ? max : 20000, required })).join('')}<button class="btn">Save</button> <a href="${base}">Clear form</a></form></div>`;
  return page(titles[table], nav + editor + tableHtml(['Record', 'Content', 'Actions'], items.map(row => [
    e(row[primary]), e(row.title || row.name || row.question || row.label || row.svalue || ''),
    `<a class="btn" href="${base}${queryJoin}edit=${encodeURIComponent(row[primary])}">Edit</a> ` + actionForm(token, { action: 'delete', [primary === 'slug' ? 'original_slug' : 'id']: row[primary] }, 'Delete', true)
  ])));
}
