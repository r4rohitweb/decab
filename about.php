<?php
require_once __DIR__ . '/includes/config.php';
$settings = getSettings($pdo);
$page = getPage($pdo, 'about');
$pageTitle = setting($settings, 'about_hero_title', 'About Us');
require __DIR__ . '/includes/header.php';
?>

<section class="inner-hero">
    <div class="container">
        <div class="breadcrumb"><a href="index.php">Home</a><span>/</span><span>About Us</span></div>
        <h1><?= e(setting($settings, 'about_hero_title', 'About ' . setting($settings, 'site_name', 'DeCab'))) ?></h1>
        <p><?= e(setting($settings, 'about_hero_subtitle', 'A simple, local cab enquiry service built around clear communication, careful driving and quick callbacks.')) ?></p>
    </div>
</section>

<main class="inner-page">
    <section class="container inner-split">
        <div>
            <p class="eyebrow dark">Who we are</p>
            <h2><?= e(setting($settings, 'about_intro_title', 'Reliable cab support for everyday travel')) ?></h2>
            <p><?= e(setting($settings, 'about_short', 'Trusted local cab service for city rides, airport transfers and outstation trips.')) ?></p>
            <p><?= e(setting($settings, 'about_intro_text', 'Our website is designed to make cab enquiries easy. You send your basic details, pickup location and travel notes. We review the request and call you back before confirming anything.')) ?></p>
            <p>Whether it is a city ride, airport transfer, hourly rental or outstation enquiry, our focus is to keep the process personal and direct.</p>
        </div>
        <div class="highlight-box">
            <h3><?= e(setting($settings, 'about_highlight_title', 'What makes us different?')) ?></h3>
            <p><?= e(setting($settings, 'about_highlight_text', 'No complicated app flow, no hidden steps. A real person checks your enquiry and confirms the ride details with you.')) ?></p>
            <a href="index.php#book" class="btn">Send Ride Enquiry</a>
        </div>
    </section>

    <section class="content-band light-band">
        <div class="container value-grid">
            <article class="value-card">
                <span>01</span>
                <h3>Our Mission</h3>
                <p>To make local cab enquiries faster, clearer and more dependable for every customer.</p>
            </article>
            <article class="value-card">
                <span>02</span>
                <h3>Our Vision</h3>
                <p>To become a trusted local travel partner for city rides, airport transfers and planned trips.</p>
            </article>
            <article class="value-card">
                <span>03</span>
                <h3>Our Values</h3>
                <p>Honesty, punctuality, clean communication and respect for every passenger.</p>
            </article>
        </div>
    </section>

    <section class="container long-content">
        <h2>How we handle your enquiry</h2>
        <?php if (!empty($page['content'])): ?>
        <div class="card info-page" style="margin-bottom:24px;">
            <?= $page['content'] ?>
        </div>
        <?php endif; ?>
        <p>When you submit the enquiry form, your details are saved for the admin team. We look at your pickup location and notes, then call you to understand the full route, timing and any specific requirement such as luggage, return ride or airport reporting time.</p>
        <p>This helps us avoid wrong assumptions. Instead of forcing you to fill a long form, we collect only what is needed first and confirm the rest over a quick call.</p>
        <div class="process-list">
            <div><b>Step 1</b><span>You submit your contact and pickup details.</span></div>
            <div><b>Step 2</b><span>We review the enquiry and call you back.</span></div>
            <div><b>Step 3</b><span>Ride timing, route and fare are confirmed clearly.</span></div>
            <div><b>Step 4</b><span>You travel with confidence after confirmation.</span></div>
        </div>
    </section>

    <section class="inner-cta">
        <div class="container">
            <h2>Ready to plan your ride?</h2>
            <p>Send your enquiry now and we will call you back with the next steps.</p>
            <a href="index.php#book" class="btn">Send Ride Enquiry</a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
