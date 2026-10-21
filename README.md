# DeCab on Netlify

The Netlify version runs on Node.js Functions with either hosted MySQL or a
small SQLite database persisted in Netlify Blobs.
Public pages retain the existing design, content, and `.php` links; these URLs
are handled by JavaScript, so PHP is not required on Netlify. The original PHP
files remain available for XAMPP and are **not published**.

## Deployed Netlify storage

For `decab.netlify.app`, `STORAGE_BACKEND=netlify-blobs` selects the built-in
persistent backend. It does not need MySQL credentials or a separate hosting
account. A private, site-wide store named `decab-database-v1` holds `site.sqlite`.
This database is independent of deployments: deploying again does not reset
bookings or content. `SESSION_SECRET` and `COOKIE_SECURE=true` are still required.
The original MySQL connection remains available when `STORAGE_BACKEND` is unset.

The data import copies the local website tables, including existing administrator
password hashes; it does not copy active sessions. SQLite reads and writes run
inside an isolated snapshot for each request. Strong reads and ETag conditional
writes prevent concurrent requests from overwriting each other's changes. A
conflict retries the entire request; a response reporting success is sent only
after persistence succeeds. Storage failure returns HTTP 503.

This storage mode is intended for a small site with infrequent writes. Each
write saves the database snapshot, with a configured maximum of 20 MB. Move to
hosted MySQL if data size or concurrent usage grows. Download `site.sqlite` from
Netlify's Blobs dashboard for backups. It contains private customer information
and administrator password hashes; keep backups out of public folders and Git.
Never replace the live snapshot with a starter database when redeploying.

For local Netlify Blobs emulation use Netlify CLI. `npm run dev` continues to use
the local MySQL backend. The following MySQL instructions apply only if you
choose that backend instead of built-in storage.

## 1. Prepare a hosted MySQL database

Use a MySQL-compatible database that accepts connections from Netlify Functions.
Your XAMPP `localhost` database cannot be reached from Netlify. Obtain the host,
port, database name, username, password, and TLS certificate requirements from
your database provider. Configure network access for your deployment according
to that provider's instructions. A provider's private/internal hostname may not
be reachable from Netlify.

Copy `.env.example` to `.env` locally and fill in your hosted database details.
Keep TLS enabled (`DB_SSL=true`); if your provider supplies a CA certificate,
put its PEM text in `DB_SSL_CA`, with newlines represented as `\n` if necessary.
Certificate verification remains enabled.

Install dependencies:

```sh
npm ci
```

For a **new, empty database**, create the tables and starter content:

```sh
npm run db:setup
```

For your **existing website data**, export `decab_db` with phpMyAdmin and import
it into the hosted database first. If your hosting assigns a different database
name, omit the export's `CREATE DATABASE` and `USE decab_db` statements. Then add
only the Netlify runtime tables:

```sh
npm run db:migrate
```

Do not import the starter schema over existing data. `db:setup` refuses a
nonempty database. Do not rerun the old update SQL files to migrate an existing
site; they contain starter content that can duplicate existing rows.

For a new database, temporarily add `ADMIN_USERNAME` and `ADMIN_PASSWORD` to
your local `.env` file (password at least 12 characters, at most 72 bytes), run:

```sh
npm run admin:create
```

Then remove `ADMIN_PASSWORD`. Fresh setup does not install a default password.
Existing PHP bcrypt administrator accounts continue to work after import.
Use the admin Change Password screen to update an existing password.

## 2. Configure the existing Netlify site

In the Netlify project for **decab.netlify.app**, add these environment variables
under **Project configuration → Environment variables**, available to Functions:

| Variable | Value |
| --- | --- |
| `DB_HOST` | Public hostname of your hosted MySQL server |
| `DB_PORT` | Provider's port, typically `3306` |
| `DB_NAME` | Hosted database name |
| `DB_USER` | Hosted database user |
| `DB_PASS` | Hosted database password |
| `DB_SSL` | `true` |
| `DB_SSL_CA` | Provider CA certificate, only if required |
| `SESSION_SECRET` | Random secret generated below |
| `COOKIE_SECURE` | `true` |

Generate the session secret locally:

```sh
node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
```

Keep credentials out of source control, browser scripts, and chat messages.
`netlify.toml` sets the build command to `npm run build`, publish directory to
`dist`, and Functions directory to `netlify/functions`. Set the Netlify base
directory to this project folder (leave it empty if the project is at the
repository root). Commit and push these changes to the branch connected to your
existing Netlify project, then trigger a new deployment. Changing environment
variables requires a new deployment.

Deploy through the connected Git repository or the Netlify CLI with Functions
support. Dragging only the `dist` folder into Netlify will omit the backend.

Netlify's official references: [Functions configuration](https://docs.netlify.com/build/functions/configuration/)
and [Functions environment variables](https://docs.netlify.com/build/functions/environment-variables/).

## 3. Check the deployment

- Open `/`, `/about.php`, `/services.php`, `/pricing.php`, `/faq.php`, and `/contact.php`.
- Submit a ride enquiry. Check the receipt and its entry in `/admin/index.php`.
- Submit a contact message and check `/admin/messages.php`.
- Sign in at `/admin/login.php`; check content, settings, cab management, and logout.
- A database/configuration failure returns HTTP 503. Inspect Netlify's Function
  logs for the underlying error code. `ECONNREFUSED`/`ETIMEDOUT` indicate hostname,
  port, or network access problems; `ER_ACCESS_DENIED_ERROR` indicates credentials;
  `ER_NO_SUCH_TABLE` indicates missing imported tables or runtime migration.

The public site intentionally waits for a working database; it does not show
fake booking success when persistence fails. Receipt links include a signature
to prevent guessing an enquiry ID to read another customer's contact details.
Administrator sessions and login throttling are stored in MySQL. Logout and
password changes revoke sessions. All changes require POST and CSRF validation.

## Local development and tests

For local XAMPP MySQL, use `DB_HOST=127.0.0.1`, `DB_SSL=false`, and
`COOKIE_SECURE=false` **only in your local `.env`**. For the existing local DB,
run `npm run db:migrate` before using admin login in the Node version.

```sh
npm run dev
npm test
npm run build
```

The Node development site is at `http://localhost:8888`. The existing PHP/XAMPP
site continues to use `includes/config.php` independently.

Run the integration suite against local MySQL from PowerShell:

```powershell
$env:TEST_MYSQL = 'true'
npm test
```

Integration tests use `TEST_DB_HOST`, `TEST_DB_PORT`, `TEST_DB_USER`, and
`TEST_DB_PASS` (defaults: `127.0.0.1:3306`, `root`, empty password). The test user
must have permission to create databases. Tests create a randomly named
`decab_netlify_test_*` database and remove that database afterwards; they do not
modify `decab_db`. Without `TEST_MYSQL=true`, these tests are skipped.

Admin enquiry/message listings show the latest 500 records; content listings
show up to 500 items. About-page HTML is sanitized before display. Bookings
prevent deleting a cab with enquiry history; mark it busy to hide it instead.
