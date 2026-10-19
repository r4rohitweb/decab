<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

// --- Enquiry actions ---
if (isset($_GET['action'], $_GET['id'])) {
    verifyCsrf();
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if (in_array($action, ['confirmed', 'completed', 'cancelled'], true)) {
        $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
        $stmt->execute([$action, $id]);
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
    }
    header('Location: index.php');
    exit;
}

$stats = [
    'total' => (int)$pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
    'pending' => (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE status = "pending"')->fetchColumn(),
    'confirmed' => (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE status = "confirmed"')->fetchColumn(),
    'completed' => (int)$pdo->query('SELECT COUNT(*) FROM bookings WHERE status = "completed"')->fetchColumn(),
];

$bookings = $pdo->query('SELECT b.*, c.name AS cab_name FROM bookings b JOIN cabs c ON c.id = b.cab_id ORDER BY b.created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ride Enquiries - DeCab Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../includes/admin-header.php'; ?>

<div class="container">
    <h2 class="section-title">Ride Enquiries</h2>

    <div class="stats">
        <div class="stat-card"><h3><?= $stats['total'] ?></h3><p>Total Enquiries</p></div>
        <div class="stat-card"><h3><?= $stats['pending'] ?></h3><p>New / Pending</p></div>
        <div class="stat-card"><h3><?= $stats['confirmed'] ?></h3><p>Confirmed</p></div>
        <div class="stat-card"><h3><?= $stats['completed'] ?></h3><p>Completed</p></div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="alert success"><?= e($_GET['msg']) ?></div>
    <?php endif; ?>

    <table class="admin-table">
        <tr>
            <th>ID</th><th>Customer</th><th>Pickup & Details</th><th>Status</th><th>Actions</th>
        </tr>
        <?php if (!$bookings): ?>
        <tr><td colspan="5" style="text-align:center;padding:30px;">No ride enquiries yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($bookings as $b): ?>
        <tr>
            <td>#<?= (int)$b['id'] ?></td>
            <td>
                <?= e($b['customer_name']) ?><br>
                <?php if (!empty($b['customer_email'])): ?><small><?= e($b['customer_email']) ?></small><br><?php endif; ?>
                <small><?= e($b['customer_phone']) ?></small>
            </td>
            <td>
                <b><?= e($b['pickup']) ?></b>
                <?php if (!empty($b['notes'])): ?><br><small><?= e($b['notes']) ?></small><?php endif; ?>
            </td>
            <td class="status-<?= e($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></td>
            <td class="actions">
                <?php if ($b['status'] === 'pending'): ?>
                    <a class="confirm" href="index.php?action=confirmed&id=<?= (int)$b['id'] ?>&<?= csrfQuery() ?>">Confirm</a>
                    <a class="cancel" href="index.php?action=cancelled&id=<?= (int)$b['id'] ?>&<?= csrfQuery() ?>">Cancel</a>
                <?php elseif ($b['status'] === 'confirmed'): ?>
                    <a class="complete" href="index.php?action=completed&id=<?= (int)$b['id'] ?>&<?= csrfQuery() ?>">Complete</a>
                    <a class="cancel" href="index.php?action=cancelled&id=<?= (int)$b['id'] ?>&<?= csrfQuery() ?>">Cancel</a>
                <?php endif; ?>
                <a class="delete" href="index.php?action=delete&id=<?= (int)$b['id'] ?>&<?= csrfQuery() ?>" onclick="return confirm('Delete this enquiry?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
