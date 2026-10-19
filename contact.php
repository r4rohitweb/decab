<?php
require_once __DIR__ . '/includes/config.php';
$settings = getSettings($pdo);
$sent = false;
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $message === '') {
        $error = 'Please enter your name and message.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, phone, message) VALUES (?,?,?,?)');
        $stmt->execute([$name, $email, $phone, $message]);
        $sent = true;
    }
}
$pageTitle = setting($settings, 'contact_hero_title', 'Contact Us');
require __DIR__ . '/includes/header.php';
?>

<section class="inner-hero">
    <div class="container">
        <div class="breadcrumb"><a href="index.php">Home</a><span>/</span><span>Contact Us</span></div>
        <h1><?= e(setting($settings, 'contact_hero_title', 'Contact Us')) ?></h1>
        <p><?= e(setting($settings, 'contact_hero_subtitle', 'Have a question, ride request or service enquiry? Send a message and we will respond as soon as possible.')) ?></p>
    </div>
</section>

<main class="inner-page">
    <section class="container contact-layout">
        <div class="contact-info-panel">
            <p class="eyebrow dark">Reach us</p>
            <h2><?= e(setting($settings, 'contact_intro_title', 'We are here to help with your cab enquiry')) ?></h2>
            <p><?= e(setting($settings, 'contact_intro_text', 'Use the contact form for general questions, service details or ride-related support.')) ?></p>
            <div class="contact-list">
                <p><b>Phone</b><span><?= e($settings['phone'] ?? '') ?></span></p>
                <p><b>Email</b><span><?= e($settings['email'] ?? '') ?></span></p>
                <p><b>Address</b><span><?= e($settings['address'] ?? '') ?></span></p>
            </div>
            <a href="index.php#book" class="btn">Ride Enquiry</a>
        </div>

        <div class="card contact-form-card">
            <?php if ($sent): ?>
                <div class="alert success">Thank you. Your message has been received.</div>
            <?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <?= csrfField() ?>
                <div class="form-row">
                    <div><label>Your Name *</label><input type="text" name="name" value="<?= e($_POST['name'] ?? '') ?>" required></div>
                    <div><label>Phone</label><input type="tel" name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></div>
                </div>
                <label>Email</label>
                <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>">
                <label>Message *</label>
                <textarea name="message" rows="7" required><?= e($_POST['message'] ?? '') ?></textarea>
                <button type="submit" class="btn">Send Message</button>
            </form>
        </div>
    </section>

    <section class="content-band light-band">
        <div class="container value-grid">
            <article class="value-card">
                <span>Call</span>
                <h3>For urgent rides</h3>
                <p>Calling is best when your travel is immediate or time-sensitive.</p>
            </article>
            <article class="value-card">
                <span>Enquiry</span>
                <h3>For planned rides</h3>
                <p>Use the ride enquiry form for route, date, timing and trip-specific requests.</p>
            </article>
            <article class="value-card">
                <span>Message</span>
                <h3>For general questions</h3>
                <p>Use this page for service questions, feedback and business communication.</p>
            </article>
        </div>
    </section>

    <section class="container long-content">
        <h2>What happens after you contact us?</h2>
        <p>Your message is stored in the admin panel. If it is a ride-related request, we may call you to confirm pickup location, destination, travel timing and any special details needed before accepting the ride.</p>
        <p>For simple questions, we respond with the information you need. Please include a correct phone number or email so we can reach you easily.</p>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
