<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'decab_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_DEBUG', false);

require_once __DIR__ . '/functions.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die(APP_DEBUG ? 'Database connection failed: ' . e($e->getMessage()) : 'Service temporarily unavailable. Please try again later.');
}
