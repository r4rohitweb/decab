<?php
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function sendSecurityHeaders() {
    if (headers_sent()) return;
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

sendSecurityHeaders();

function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function csrfQuery() {
    return 'csrf_token=' . urlencode(csrfToken());
}

function verifyCsrf($token = null) {
    $token = $token ?? ($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid security token. Please go back and try again.');
    }
}

function getCabs($pdo, $onlyAvailable = false) {
    $sql = 'SELECT * FROM cabs' . ($onlyAvailable ? ' WHERE status = "available"' : '') . ' ORDER BY id';
    return $pdo->query($sql)->fetchAll();
}

function getCab($pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM cabs WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}
function getSettings($pdo) {
    $rows = $pdo->query('SELECT skey, svalue FROM settings')->fetchAll();
    $out = [];
    foreach ($rows as $r) $out[$r['skey']] = $r['svalue'];
    return $out;
}

function setting($settings, $key, $default = '') {
    return $settings[$key] ?? $default;
}

function getPage($pdo, $slug) {
    $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ?');
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getServices($pdo) {
    return $pdo->query('SELECT * FROM services ORDER BY id')->fetchAll();
}

function getFaqs($pdo) {
    return $pdo->query('SELECT * FROM faqs ORDER BY id')->fetchAll();
}

function getContent($pdo, $table) {
    $allowed = ['stats', 'steps', 'features', 'testimonials'];
    if (!in_array($table, $allowed, true)) return [];
    return $pdo->query("SELECT * FROM $table ORDER BY sort, id")->fetchAll();
}

function calcFare($cab, $km) {
    $km = max(0, (float)$km);
    return round($cab['base_fare'] + $km * (float)$cab['fare_per_km'], 2);
}

function calculateAdminHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
