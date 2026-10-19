<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if (isset($_GET['read'])) {
    verifyCsrf();
    $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([(int)$_GET['read']]);
    header('Location: messages.php');
    exit;
}
if (isset($_GET['delete'])) {
    verifyCsrf();
    $pdo->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([(int)$_GET['delete']]);
    header('Location: messages.php?msg=Message deleted');
    exit;
}
$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'Contact Messages';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages — DeCab Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../includes/admin-header.php'; ?>

<div class="container" style="max-width:900px;">
    <h2 class="section-title">Contact Messages</h2>
    <?php if (isset($_GET['msg'])): ?><div class="alert success"><?= e($_GET['msg']) ?></div><?php endif; ?>

    <?php if (!$messages): ?>
    <div class="card"><p style="text-align:center;">No messages yet.</p></div>
    <?php endif; ?>

    <?php foreach ($messages as $m): ?>
    <div class="card" style="margin-bottom:14px;border-left:4px solid <?= $m['is_read'] ? '#ccc' : 'var(--primary)' ?>;">
        <p style="color:#888;font-size:.85rem;">#<?= (int)$m['id'] ?> — <?= e(date('d M Y, h:i A', strtotime($m['created_at']))) ?> <?= !$m['is_read'] ? '<span class="badge busy">NEW</span>' : '' ?></p>
        <p><b><?= e($m['name']) ?></b>
            <?php if ($m['phone']): ?> • 📞 <?= e($m['phone']) ?><?php endif; ?>
            <?php if ($m['email']): ?> • ✉️ <?= e($m['email']) ?><?php endif; ?>
        </p>
        <p style="margin-top:8px;line-height:1.6;"><?= nl2br(e($m['message'])) ?></p>
        <p class="actions" style="margin-top:10px;">
            <?php if (!$m['is_read']): ?><a class="confirm" href="messages.php?read=<?= (int)$m['id'] ?>&<?= csrfQuery() ?>">Mark as Read</a><?php endif; ?>
            <a class="delete" href="messages.php?delete=<?= (int)$m['id'] ?>&<?= csrfQuery() ?>" onclick="return confirm('Delete this message?')">Delete</a>
        </p>
    </div>
    <?php endforeach; ?>
</div>

</body>
</html>
