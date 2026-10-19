<?php
// Public site header — expects $pdo, $settings loaded by the page
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? ($settings['site_name'] ?? 'DeCab')) ?></title>
<link rel="stylesheet" href="<?= $headerDir ?? '' ?>assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="top-strip">
        <span>Call: <?= e($settings['phone'] ?? '') ?></span>
        <span><?= e($settings['email'] ?? '') ?></span>
    </div>
    <div class="nav-shell">
        <a class="site-logo" href="<?= $headerDir ?? '' ?>index.php">
            <span class="mark">D</span>
            <span class="brand-text"><?= e($settings['site_name'] ?? 'DeCab') ?><small>Cab Service</small></span>
        </a>
        <nav class="site-nav">
            <a href="<?= $headerDir ?? '' ?>index.php">Home</a>
            <a href="<?= $headerDir ?? '' ?>about.php">About</a>
            <a href="<?= $headerDir ?? '' ?>services.php">Services</a>
            <a href="<?= $headerDir ?? '' ?>pricing.php">Fare Enquiry</a>
            <a href="<?= $headerDir ?? '' ?>faq.php">FAQ</a>
            <a href="<?= $headerDir ?? '' ?>contact.php">Contact</a>
            <a class="nav-cta" href="<?= $headerDir ?? '' ?>index.php#book">Ride Enquiry</a>
        </nav>
    </div>
</header>
