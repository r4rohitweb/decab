import mysql from 'mysql2/promise';
import bcrypt from 'bcryptjs';
import { readFile } from 'node:fs/promises';
import { databaseOptions } from '../server/db.mjs';

const mode = process.argv[2];
if (!['setup', 'migrate', 'admin'].includes(mode)) throw new Error('Use setup, migrate, or admin.');
const { waitForConnections, connectionLimit, queueLimit, ...config } = databaseOptions();
const connection = await mysql.createConnection({ ...config, multipleStatements: mode !== 'admin' });
try {
  if (mode === 'setup') {
    const [tables] = await connection.query('SHOW TABLES');
    if (tables.length) throw new Error('Database is not empty. For an existing database, use npm run db:migrate instead.');
    let schema = await readFile(new URL('../sql/schema.sql', import.meta.url), 'utf8');
    schema = schema.replace(/^CREATE DATABASE[^;]*;\s*/m, '').replace(/^USE [^;]*;\s*/m, '');
    // Never install a shared/default administrator password in a fresh database.
    schema = schema.replace(/INSERT INTO admins \(username, password\) VALUES\s*\([^;]*;/, '');
    await connection.query(schema);
    console.log('Website tables and starter content created.');
  }
  if (mode === 'setup' || mode === 'migrate') {
    await connection.query(await readFile(new URL('../sql/netlify-runtime.sql', import.meta.url), 'utf8'));
    console.log('Netlify session and login protection tables ready.');
  }
  if (mode === 'admin') {
    const username = process.env.ADMIN_USERNAME?.trim();
    const password = process.env.ADMIN_PASSWORD;
    if (!username || username.length > 50 || !password || password.length < 12 || Buffer.byteLength(password) > 72) throw new Error('Set ADMIN_USERNAME (up to 50 characters) and ADMIN_PASSWORD (12–72 bytes) in your local .env file.');
    const [existing] = await connection.execute('SELECT id FROM admins WHERE username = ?', [username]);
    if (existing.length) throw new Error('Username already exists. Use the admin Change Password screen.');
    await connection.execute('INSERT INTO admins (username, password) VALUES (?, ?)', [username, await bcrypt.hash(password, 12)]);
    console.log('Administrator created. Remove ADMIN_PASSWORD from your .env file.');
  }
} finally { await connection.end(); }
