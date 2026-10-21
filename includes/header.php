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
<script src="<?= $headerDir ?? '' ?>assets/js/site.js" defer></script>
</head>
<body>

<header class="site-header">
    <div class="top-strip">
        <a href="tel:<?= e($settings['phone'] ?? '') ?>">Call: <?= e($settings['phone'] ?? '') ?></a>
        <a href="mailto:<?= e($settings['email'] ?? '') ?>"><?= e($settings['email'] ?? '') ?></a>
    </div>
    <div class="nav-shell">
        <a class="site-logo" href="<?= $headerDir ?? '' ?>index.php">
            <span class="mark">D</span>
            <span class="brand-text"><?= e($settings['site_name'] ?? 'DeCab') ?><small>Cab Service</small></span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" aria-label="Open navigation">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span>Menu</span>
        </button>
        <nav class="site-nav" id="site-navigation" aria-label="Main navigation">
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
