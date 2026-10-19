<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrf();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([(int)$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($currentPassword, $admin['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New password and confirmation do not match.';
    } else {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE admins SET password = ? WHERE id = ?')->execute([$hash, (int)$_SESSION['admin_id']]);
        $success = 'Password changed successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password - DeCab Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../includes/admin-header.php'; ?>

<div class="container" style="max-width:720px;">
    <h2 class="section-title">Change Password</h2>

    <div class="card">
        <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

        <form method="post">
            <?= csrfField() ?>
            <label>Current Password</label>
            <input type="password" name="current_password" autocomplete="current-password" required>

            <label>New Password</label>
            <input type="password" name="new_password" autocomplete="new-password" required>

            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" autocomplete="new-password" required>

            <button type="submit" class="btn">Update Password</button>
        </form>
    </div>
</div>

</body>
</html>
