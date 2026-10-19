<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pdo->prepare('INSERT INTO services (icon, title, description) VALUES (?,?,?)')
            ->execute([trim($_POST['icon'] ?: '🚕'), trim($_POST['title']), trim($_POST['description'])]);
        header('Location: services.php?msg=Service added');
        exit;
    }
    if ($action === 'update') {
        $pdo->prepare('UPDATE services SET icon=?, title=?, description=? WHERE id=?')
            ->execute([trim($_POST['icon'] ?: '🚕'), trim($_POST['title']), trim($_POST['description']), (int)$_POST['id']]);
        header('Location: services.php?msg=Service updated');
        exit;
    }
}
if (isset($_GET['delete'])) {
    verifyCsrf();
    $pdo->prepare('DELETE FROM services WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: services.php?msg=Service deleted');
    exit;
}
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}
$services = getServices($pdo);
$pageTitle = 'Manage Services';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Services — DeCab Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../includes/admin-header.php'; ?>

<div class="container" style="max-width:900px;">
    <h2 class="section-title">Manage Services</h2>
    <?php if (isset($_GET['msg'])): ?><div class="alert success"><?= e($_GET['msg']) ?></div><?php endif; ?>

    <div class="card" style="margin-bottom:25px;">
        <h3><?= $editing ? 'Edit Service' : 'Add New Service' ?></h3>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
            <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
            <div class="form-row">
                <div><label>Icon (emoji)</label><input type="text" name="icon" value="<?= e($editing['icon'] ?? '🚕') ?>"></div>
                <div><label>Title</label><input type="text" name="title" value="<?= e($editing['title'] ?? '') ?>" required></div>
            </div>
            <label>Description</label>
            <textarea name="description" rows="3" style="width:100%;padding:11px;border:1px solid #ddd;border-radius:8px;font-size:1rem;font-family:inherit;"><?= e($editing['description'] ?? '') ?></textarea>
            <button type="submit" class="btn"><?= $editing ? 'Update Service' : 'Add Service' ?></button>
            <?php if ($editing): ?><a href="services.php" class="btn" style="background:#888;color:#fff;">Cancel</a><?php endif; ?>
        </form>
    </div>

    <table class="admin-table">
        <tr><th>ID</th><th>Icon</th><th>Title</th><th>Description</th><th>Actions</th></tr>
        <?php foreach ($services as $s): ?>
        <tr>
            <td>#<?= (int)$s['id'] ?></td>
            <td><?= e($s['icon']) ?></td>
            <td><b><?= e($s['title']) ?></b></td>
            <td><?= e($s['description']) ?></td>
            <td class="actions">
                <a class="complete" href="services.php?edit=<?= (int)$s['id'] ?>">Edit</a>
                <a class="delete" href="services.php?delete=<?= (int)$s['id'] ?>&<?= csrfQuery() ?>" onclick="return confirm('Delete this service?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
