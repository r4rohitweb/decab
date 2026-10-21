import initSqlJs from 'sql.js/dist/sql-wasm-inline.js';
import { getStore } from '@netlify/blobs';

export const STORE_NAME = 'decab-database-v1';
export const DATABASE_KEY = 'site.sqlite';
let engine;
export const sqliteEngine = () => engine ||= initSqlJs();

// The application keeps its MySQL query interface. Only these two MySQL-specific
// statements and UTC date expressions need adapting for the SQLite backend.
export function sqliteQuery(sql) {
  if (sql.startsWith('INSERT INTO login_attempts ')) {
    return `INSERT INTO login_attempts (attempt_key, attempts, expires_at)
      VALUES (?, 1, datetime('now', '+15 minutes'))
      ON CONFLICT(attempt_key) DO UPDATE SET
      attempts = CASE WHEN expires_at <= datetime('now') THEN 1 ELSE attempts + 1 END,
      expires_at = CASE WHEN expires_at <= datetime('now') THEN datetime('now', '+15 minutes') ELSE expires_at END`;
  }
  return sql.replace(/DATE_ADD\(UTC_TIMESTAMP\(\), INTERVAL (\d+) HOUR\)/g, (_, hours) => `datetime('now', '+${hours} hours')`)
    .replaceAll('UTC_TIMESTAMP()', "datetime('now')")
    .replace('ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)', 'ON CONFLICT(skey) DO UPDATE SET svalue = excluded.svalue');
}
export class SqliteConnection {
  constructor(database) { this.database = database; this.dirty = false; }
  async execute(query, values = []) {
    const sql = sqliteQuery(query);
    const statement = this.database.prepare(sql);
    try {
      statement.bind(values);
      const rows = [];
      const isRead = statement.getColumnNames().length > 0;
      while (statement.step()) rows.push(statement.getAsObject());
      if (isRead) return [rows];
      this.dirty = true;
      const affectedRows = this.database.getRowsModified();
      const insertId = this.database.exec('SELECT last_insert_rowid() AS id')[0]?.values[0][0] || 0;
      return [{ affectedRows, insertId }];
    } finally { statement.free(); }
  }
  query(sql, values) { return this.execute(sql, values); }
  async getConnection() { return this; }
  async beginTransaction() { this.database.run('BEGIN'); }
  async commit() { this.database.run('COMMIT'); }
  async rollback() { this.database.run('ROLLBACK'); }
  release() {}
}

export async function withBlobDatabase(work, store = getStore({ name: STORE_NAME, consistency: 'strong' })) {
  const SQL = await sqliteEngine();
  // Compare-and-swap protects the entire request's writes from concurrent updates.
  // A failed comparison reruns against the newest snapshot before responding.
  for (let attempt = 0; attempt < 8; attempt++) {
    const snapshot = await store.getWithMetadata(DATABASE_KEY, { type: 'arrayBuffer', consistency: 'strong' });
    if (!snapshot) throw new Error('DeCab storage has not been initialized');
    const database = new SQL.Database(new Uint8Array(snapshot.data));
    database.run('PRAGMA foreign_keys = ON');
    try {
      const connection = new SqliteConnection(database);
      const response = await work(connection);
      if (!connection.dirty || response.status >= 500) return response;
      const bytes = database.export();
      if (bytes.byteLength > 20 * 1024 * 1024) throw new Error('DeCab database has reached its configured storage limit');
      const { modified } = await store.set(DATABASE_KEY, bytes.buffer, { onlyIfMatch: snapshot.etag });
      if (modified) return response;
    } finally { database.close(); }
    await new Promise(resolve => setTimeout(resolve, 15 + Math.random() * 40));
  }
  return new Response('The service is busy. Your request was not saved. Please try again.', { status: 503, headers: { 'Content-Type': 'text/plain', 'Cache-Control': 'no-store', 'Retry-After': '2' } });
}
