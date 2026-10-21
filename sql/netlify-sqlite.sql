-- SQLite schema for the optional Netlify Blobs backend. Data is imported separately.
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS cabs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    model VARCHAR(100) DEFAULT '',
    plate_number VARCHAR(30) DEFAULT '',
    driver_name VARCHAR(100) DEFAULT '',
    driver_phone VARCHAR(30) DEFAULT '',
    seats INT DEFAULT 4,
    fare_per_km DECIMAL(10,2) NOT NULL DEFAULT 12.00,
    base_fare DECIMAL(10,2) NOT NULL DEFAULT 50.00,
    image VARCHAR(255) DEFAULT '',
    status TEXT DEFAULT 'available'
);

CREATE TABLE IF NOT EXISTS settings (
    skey VARCHAR(50) PRIMARY KEY,
    svalue TEXT
);

CREATE TABLE IF NOT EXISTS pages (
    slug VARCHAR(50) PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    content TEXT
);

CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    icon VARCHAR(10) DEFAULT '🚕',
    title VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS faqs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question VARCHAR(255) NOT NULL,
    answer TEXT
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT '',
    phone VARCHAR(20) DEFAULT '',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    svalue VARCHAR(50) NOT NULL,
    label VARCHAR(100) NOT NULL,
    sort INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    step_no INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    sort INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS features (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    icon VARCHAR(10) DEFAULT '⭐',
    title VARCHAR(100) NOT NULL,
    description TEXT,
    sort INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS testimonials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100) DEFAULT '',
    rating INT DEFAULT 5,
    review TEXT,
    sort INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cab_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) DEFAULT '',
    customer_phone VARCHAR(20) NOT NULL,
    trip_type VARCHAR(50) DEFAULT 'Local ride',
    passenger_count INT DEFAULT 1,
    pickup VARCHAR(255) NOT NULL,
    drop_location VARCHAR(255) NOT NULL,
    ride_date DATE DEFAULT NULL,
    ride_time TIME DEFAULT NULL,
    distance_km DECIMAL(10,2) DEFAULT 0,
    fare DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    status TEXT DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cab_id) REFERENCES cabs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admin_sessions (
  token_hash CHAR(64) PRIMARY KEY,
  admin_id INT NOT NULL,
  expires_at DATETIME NOT NULL,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS login_attempts (
  attempt_key CHAR(64) PRIMARY KEY,
  attempts INT NOT NULL DEFAULT 1,
  expires_at DATETIME NOT NULL
);