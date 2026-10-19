<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pdo->prepare('INSERT INTO faqs (question, answer) VALUES (?,?)')
            ->execute([trim($_POST['question']), trim($_POST['answer'])]);
        header('Location: faqs.php?msg=FAQ added');
        exit;
    }
    if ($action === 'update') {
        $pdo->prepare('UPDATE faqs SET question=?, answer=? WHERE id=?')
            ->execute([trim($_POST['question']), trim($_POST['answer']), (int)$_POST['id']]);
        header('Location: faqs.php?msg=FAQ updated');
        exit;
    }
}
if (isset($_GET['delete'])) {
    verifyCsrf();
    $pdo->prepare('DELETE FROM faqs WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: faqs.php?msg=FAQ deleted');
    exit;
}
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM faqs WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}
$faqs = getFaqs($pdo);
$pageTitle = 'Manage FAQs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage FAQs — DeCab Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../includes/admin-header.php'; ?>

<div class="container" style="max-width:900px;">
    <h2 class="section-title">Manage FAQs</h2>
    <?php if (isset($_GET['msg'])): ?><div class="alert success"><?= e($_GET['msg']) ?></div><?php endif; ?>

    <div class="card" style="margin-bottom:25px;">
        <h3><?= $editing ? 'Edit FAQ' : 'Add New FAQ' ?></h3>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
            <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
            <label>Question</label>
            <input type="text" name="question" value="<?= e($editing['question'] ?? '') ?>" required>
            <label>Answer</label>
            <textarea name="answer" rows="3" style="width:100%;padding:11px;border:1px solid #ddd;border-radius:8px;font-size:1rem;font-family:inherit;"><?= e($editing['answer'] ?? '') ?></textarea>
            <button type="submit" class="btn"><?= $editing ? 'Update FAQ' : 'Add FAQ' ?></button>
            <?php if ($editing): ?><a href="faqs.php" class="btn" style="background:#888;color:#fff;">Cancel</a><?php endif; ?>
        </form>
    </div>

    <table class="admin-table">
        <tr><th>ID</th><th>Question</th><th>Answer</th><th>Actions</th></tr>
        <?php foreach ($faqs as $f): ?>
        <tr>
            <td>#<?= (int)$f['id'] ?></td>
            <td><b><?= e($f['question']) ?></b></td>
            <td><?= e($f['answer']) ?></td>
            <td class="actions">
                <a class="complete" href="faqs.php?edit=<?= (int)$f['id'] ?>">Edit</a>
                <a class="delete" href="faqs.php?delete=<?= (int)$f['id'] ?>&<?= csrfQuery() ?>" onclick="return confirm('Delete this FAQ?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
