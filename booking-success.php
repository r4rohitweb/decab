<?php
require_once __DIR__ . '/includes/config.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT b.*, c.name AS cab_name, c.driver_name, c.driver_phone FROM bookings b JOIN cabs c ON c.id = b.cab_id WHERE b.id = ?');
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: index.php');
    exit;
}

$settings = getSettings($pdo);
$pageTitle = 'Enquiry Received';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h2 class="section-title">Ride Enquiry Received</h2>
    <div class="card" style="max-width:560px;margin:0 auto;">
        <div class="alert success">Your enquiry <b>#<?= (int)$booking['id'] ?></b> has been received. We will call you to confirm availability and fare.</div>
        <p><b>Name:</b> <?= e($booking['customer_name']) ?></p>
        <p><b>Email:</b> <?= e($booking['customer_email'] ?? '') ?></p>
        <p><b>Phone:</b> <?= e($booking['customer_phone']) ?></p>
        <p><b>Pickup Location:</b> <?= e($booking['pickup']) ?></p>
        <p style="margin-top:10px;">We will contact you to collect the remaining trip details and confirm the fare.</p>
        <?php if (!empty($booking['notes'])): ?>
        <p><b>Extra Details:</b> <?= nl2br(e($booking['notes'])) ?></p>
        <?php endif; ?>
        <a href="index.php#book" class="btn">Send Another Enquiry</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
