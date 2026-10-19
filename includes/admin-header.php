<?php
// Admin shared header — expects $pdo
$unread = (int)$pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();
?>
<aside class="admin-header">
    <div class="admin-brand">
        <span class="admin-mark">D</span>
        <div>
            <b>DeCab</b>
            <small>Admin Panel</small>
        </div>
    </div>

    <nav class="admin-nav">
        <a href="index.php"><span>Dashboard</span></a>
        <a href="cabs.php"><span>Cabs</span></a>
        <a href="services.php"><span>Services</span></a>
        <a href="faqs.php"><span>FAQs</span></a>
        <a href="sections.php"><span>Homepage Sections</span></a>
        <a href="pages.php"><span>Pages</span></a>
        <a href="settings.php"><span>Website Settings</span></a>
        <a href="messages.php"><span>Messages</span><?= $unread ? ' <span class="badge busy">' . $unread . '</span>' : '' ?></a>
        <a href="change-password.php"><span>Change Password</span></a>
    </nav>

    <div class="admin-user">
        <small>Signed in as</small>
        <b><?= e($_SESSION['admin_name']) ?></b>
        <a href="logout.php">Logout</a>
    </div>
</aside>
