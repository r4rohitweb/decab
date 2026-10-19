<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$groups = [
    'Business Details' => [
        'site_name' => ['Site Name', 'text', 'DeCab'],
        'phone' => ['Contact Phone', 'text', '9876543210'],
        'email' => ['Contact Email', 'text', 'info@decab.com'],
        'address' => ['Office Address', 'textarea', 'MG Road, Bengaluru'],
    ],
    'Homepage' => [
        'hero_eyebrow' => ['Hero Small Text', 'text', 'Reliable local cab service'],
        'hero_title' => ['Hero Title', 'text', 'Book Your Ride in Seconds'],
        'hero_subtitle' => ['Hero Subtitle', 'textarea', 'Send us your pickup, drop, date and time. We will call back quickly to confirm your cab.'],
        'home_panel_title' => ['Hero Panel Title', 'text', 'Share your trip details'],
        'home_panel_text' => ['Hero Panel Text', 'textarea', 'Tell us pickup, drop, date and time. We will call back with availability and fare.'],
        'about_short' => ['Homepage Short About', 'textarea', 'Trusted local cab service for city rides, airport transfers and outstation trips.'],
        'home_intro_title' => ['Homepage Intro Title', 'text', 'Tell us your route. We confirm the ride.'],
        'home_intro_text' => ['Homepage Intro Text', 'textarea', 'Choose the type of trip you need and send the details. We review the route and call you back with confirmation.'],
        'enquiry_title' => ['Enquiry Section Title', 'text', 'Share your trip details'],
        'enquiry_text' => ['Enquiry Section Text', 'textarea', 'Submit your basic details and pickup location. We will call you to understand the trip and confirm the ride.'],
    ],
    'About Page' => [
        'about_hero_title' => ['Hero Title', 'text', 'About DeCab'],
        'about_hero_subtitle' => ['Hero Subtitle', 'textarea', 'A simple, local cab enquiry service built around clear communication, careful driving and quick callbacks.'],
        'about_intro_title' => ['Intro Title', 'text', 'Reliable cab support for everyday travel'],
        'about_intro_text' => ['Intro Text', 'textarea', 'Our website is designed to make cab enquiries easy. You send your basic details, pickup location and travel notes. We review the request and call you back before confirming anything.'],
        'about_highlight_title' => ['Highlight Title', 'text', 'What makes us different?'],
        'about_highlight_text' => ['Highlight Text', 'textarea', 'No complicated app flow, no hidden steps. A real person checks your enquiry and confirms the ride details with you.'],
    ],
    'Services Page' => [
        'services_hero_title' => ['Hero Title', 'text', 'Cab Services'],
        'services_hero_subtitle' => ['Hero Subtitle', 'textarea', 'Flexible cab enquiry support for city travel, airport trips, hourly needs and outstation plans.'],
        'services_intro_title' => ['Intro Title', 'text', 'Choose the service, share the details, and we confirm the ride'],
        'services_intro_text' => ['Intro Text', 'textarea', 'Every ride starts with a simple enquiry. You tell us where you want to start and add any travel notes.'],
    ],
    'Fare Enquiry Page' => [
        'fare_hero_title' => ['Hero Title', 'text', 'Fare Enquiry'],
        'fare_hero_subtitle' => ['Hero Subtitle', 'textarea', 'Tell us your route and travel need. We confirm fare only after reviewing the trip details with you.'],
        'fare_intro_title' => ['Intro Title', 'text', 'No public rate confusion. We confirm after route review.'],
        'fare_intro_text' => ['Intro Text', 'textarea', 'Cab fares depend on pickup area, destination, travel time, waiting time, trip type and return plan.'],
    ],
    'Contact Page' => [
        'contact_hero_title' => ['Hero Title', 'text', 'Contact Us'],
        'contact_hero_subtitle' => ['Hero Subtitle', 'textarea', 'Have a question, ride request or service enquiry? Send a message and we will respond as soon as possible.'],
        'contact_intro_title' => ['Contact Box Title', 'text', 'We are here to help with your cab enquiry'],
        'contact_intro_text' => ['Contact Box Text', 'textarea', 'Use the contact form for general questions, service details or ride-related support.'],
    ],
    'FAQ Page' => [
        'faq_hero_title' => ['Hero Title', 'text', 'Frequently Asked Questions'],
        'faq_hero_subtitle' => ['Hero Subtitle', 'textarea', 'Answers to common questions about ride enquiries, fare confirmation, contact process and service details.'],
        'faq_intro_title' => ['Intro Title', 'text', 'Understand the enquiry process before you send your request'],
        'faq_intro_text' => ['Intro Text', 'textarea', 'We keep the public form short so customers can contact us quickly. The remaining ride details are confirmed through callback before the ride is accepted.'],
    ],
    'Footer' => [
        'footer_cta_eyebrow' => ['Footer CTA Small Text', 'text', 'Need a cab?'],
        'footer_cta_title' => ['Footer CTA Title', 'text', 'Send an enquiry and get a quick callback.'],
    ],
];

$defaults = [];
foreach ($groups as $fields) {
    foreach ($fields as $key => $field) {
        $defaults[$key] = $field[2];
    }
}

$insertDefault = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE skey = skey');
foreach ($defaults as $key => $value) {
    $insertDefault->execute([$key, $value]);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrf();
    $stmt = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
    foreach (array_keys($defaults) as $key) {
        $stmt->execute([$key, trim($_POST[$key] ?? '')]);
    }
    header('Location: settings.php?msg=Settings saved successfully');
    exit;
}

$settings = getSettings($pdo);
$pageTitle = 'Website Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Website Settings - DeCab Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/../includes/admin-header.php'; ?>

<div class="container" style="max-width:980px;">
    <h2 class="section-title">Website Settings</h2>
    <?php if (isset($_GET['msg'])): ?><div class="alert success"><?= e($_GET['msg']) ?></div><?php endif; ?>

    <form method="post">
        <?= csrfField() ?>
        <?php foreach ($groups as $groupTitle => $fields): ?>
        <div class="card" style="margin-bottom:22px;">
            <h3 style="color:var(--dark);margin-bottom:14px;"><?= e($groupTitle) ?></h3>
            <?php foreach ($fields as $key => $field): ?>
            <label><?= e($field[0]) ?></label>
            <?php if ($field[1] === 'textarea'): ?>
            <textarea name="<?= e($key) ?>" rows="3"><?= e(setting($settings, $key, $field[2])) ?></textarea>
            <?php else: ?>
            <input type="text" name="<?= e($key) ?>" value="<?= e(setting($settings, $key, $field[2])) ?>">
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn">Save All Website Settings</button>
    </form>
</div>

</body>
</html>
