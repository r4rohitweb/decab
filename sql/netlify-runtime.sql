-- Run in the SAME database as schema.sql. Safe to run again.
CREATE TABLE IF NOT EXISTS admin_sessions (
  token_hash CHAR(64) PRIMARY KEY,
  admin_id INT NOT NULL,
  expires_at DATETIME NOT NULL,
  INDEX (expires_at),
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS login_attempts (
  attempt_key CHAR(64) PRIMARY KEY,
  attempts INT NOT NULL DEFAULT 1,
  expires_at DATETIME NOT NULL,
  INDEX (expires_at)
);
