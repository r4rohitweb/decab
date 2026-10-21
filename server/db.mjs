import mysql from 'mysql2/promise';
let pool;
export function databaseOptions(env = process.env) {
  for (const key of ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']) {
    if (env[key] === undefined || (key !== 'DB_PASS' && !env[key])) throw new Error(`Missing ${key}`);
  }
  return {
    host: env.DB_HOST, port: Number(env.DB_PORT || 3306), database: env.DB_NAME,
    user: env.DB_USER, password: env.DB_PASS, charset: 'utf8mb4',
    waitForConnections: true, connectionLimit: 2, queueLimit: 20, connectTimeout: 10000,
    ssl: env.DB_SSL === 'false' ? undefined : {
      rejectUnauthorized: true,
      ...(env.DB_SSL_CA ? { ca: env.DB_SSL_CA.replace(/\\n/g, '\n') } : {})
    }
  };
}
export function getDatabase() {
  return pool ||= mysql.createPool(databaseOptions());
}
