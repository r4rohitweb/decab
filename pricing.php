<?php
require_once __DIR__ . '/includes/config.php';
$settings = getSettings($pdo);
$pageTitle = setting($settings, 'fare_hero_title', 'Fare Enquiry');
require __DIR__ . '/includes/header.php';
?>

<section class="inner-hero">
    <div class="container">
        <div class="breadcrumb"><a href="index.php">Home</a><span>/</span><span>Fare Enquiry</span></div>
        <h1><?= e(setting($settings, 'fare_hero_title', 'Fare Enquiry')) ?></h1>
        <p><?= e(setting($settings, 'fare_hero_subtitle', 'Tell us your route and travel need. We confirm fare only after reviewing the trip details with you.')) ?></p>
    </div>
</section>

<main class="inner-page">
    <section class="container inner-split">
        <div>
            <p class="eyebrow dark">Clear fare discussion</p>
            <h2><?= e(setting($settings, 'fare_intro_title', 'No public rate confusion. We confirm after route review.')) ?></h2>
            <p><?= e(setting($settings, 'fare_intro_text', 'Cab fares depend on pickup area, destination, travel time, waiting time, trip type and return plan.')) ?></p>
            <p>Send your name, email, phone number, pickup location and extra details. We will call you back, understand the full route and confirm the final fare before the ride is accepted.</p>
        </div>
        <div class="highlight-box">
            <h3>What to add in extra details</h3>
            <p>Drop location, date, time, number of passengers, luggage, return ride plan and any special request.</p>
            <a href="index.php#book" class="btn">Ask for Fare</a>
        </div>
    </section>

    <section class="content-band light-band">
        <div class="container value-grid">
            <article class="value-card">
                <span>01</span>
                <h3>Route review</h3>
                <p>We check pickup and destination before confirming the fare.</p>
            </article>
            <article class="value-card">
                <span>02</span>
                <h3>Trip type</h3>
                <p>City ride, airport transfer, hourly rental and outstation trips are reviewed differently.</p>
            </article>
            <article class="value-card">
                <span>03</span>
                <h3>Clear confirmation</h3>
                <p>You get the fare information before your ride is confirmed.</p>
            </article>
        </div>
    </section>

    <section class="container long-content">
        <h2>How fare enquiry works</h2>
        <p>After receiving your enquiry, the admin team reads the pickup location and notes. If important trip information is missing, we ask for it during the callback. Once the route and timing are clear, we share the fare and confirmation details.</p>
        <p>This approach is better for customers because it prevents wrong estimates and avoids surprises. It also keeps the form short, so you do not need to fill a long booking form before speaking with us.</p>
        <div class="process-list">
            <div><b>Send</b><span>Submit the short enquiry form.</span></div>
            <div><b>Discuss</b><span>We call you and understand the trip.</span></div>
            <div><b>Confirm</b><span>Fare and ride details are confirmed clearly.</span></div>
        </div>
    </section>

    <section class="inner-cta">
        <div class="container">
            <h2>Need a fare for your ride?</h2>
            <p>Send the enquiry now and include your route details in the message.</p>
            <a href="index.php#book" class="btn">Send Fare Enquiry</a>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
