<?php
require_once __DIR__ . '/../includes/config.php';

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrf();
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([trim($_POST['username'] ?? '')]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($_POST['password'] ?? '', $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['username'];
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - DeCab</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-login-page">

<main class="admin-login-shell">
    <section class="admin-login-visual">
        <a class="login-back" href="../index.php">Back to Website</a>
        <div class="login-visual-content">
            <p class="eyebrow">Admin Panel</p>
            <h1>Manage enquiries, content and cab service details.</h1>
            <p>Control ride enquiries, website sections, service content, contact messages and business settings from one dashboard.</p>
        </div>
    </section>

    <section class="admin-login-form-wrap">
        <div class="login-card">
            <div class="login-brand">
                <span class="mark">D</span>
                <div>
                    <h2>DeCab Admin</h2>
                    <p>Sign in to continue</p>
                </div>
            </div>

            <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

            <form method="post">
                <?= csrfField() ?>
                <label>Username</label>
                <input type="text" name="username" autocomplete="username" required>

                <label>Password</label>
                <input type="password" name="password" autocomplete="current-password" required>

                <button type="submit" class="btn login-submit">Login to Dashboard</button>
            </form>
        </div>
    </section>
</main>

</body>
</html>
