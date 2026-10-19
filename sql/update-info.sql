-- Information website upgrade (run on existing decab_db)
USE decab_db;

CREATE TABLE IF NOT EXISTS settings (
    skey VARCHAR(50) PRIMARY KEY,
    svalue TEXT
);

INSERT INTO settings (skey, svalue) VALUES
('site_name', 'DeCab'),
('hero_title', 'Book Your Ride in Seconds'),
('hero_subtitle', 'Reliable, comfortable and affordable taxi service — available when you need it.'),
('phone', '9876543210'),
('email', 'info@decab.com'),
('address', '12, MG Road, Bengaluru, Karnataka 560001'),
('about_short', 'DeCab is your trusted local taxi partner. One call, one tap — and a clean cab with a friendly driver is on the way. We focus on safety, punctuality and honest pricing.')
ON DUPLICATE KEY UPDATE skey = skey;

CREATE TABLE IF NOT EXISTS pages (
    slug VARCHAR(50) PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    content TEXT
);

INSERT INTO pages (slug, title, content) VALUES
('about', 'About Us', '<p>Welcome to <b>DeCab Taxi Services</b> — your trusted travel partner in the city. We have been serving customers with clean cabs, professional drivers and transparent pricing.</p><p>Our mission is simple: make every ride safe, comfortable and on time. Whether it is a daily office commute, an airport drop or an outstation trip, our drivers know every corner of the city.</p><h3>Why choose us?</h3><ul><li>Clean, well-maintained cabs</li><li>Verified and experienced drivers</li><li>Transparent fares — no hidden charges</li><li>24x7 customer support</li></ul>')
ON DUPLICATE KEY UPDATE slug = slug;

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    icon VARCHAR(10) DEFAULT '🚕',
    title VARCHAR(100) NOT NULL,
    description TEXT
);

INSERT INTO services (icon, title, description) VALUES
('🚕', 'City Rides', 'Quick and comfortable rides anywhere within the city at honest meter-based fares.'),
('✈️', 'Airport Transfer', 'On-time pickup and drop for airports with luggage-friendly cabs.'),
('🛣️', 'Outstation Trips', 'Plan a weekend getaway or business trip to nearby cities with our outstation service.'),
('⏱️', 'Hourly Rental', 'Hire a cab with driver by the hour for shopping, meetings or sightseeing.');

CREATE TABLE IF NOT EXISTS faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT
);

INSERT INTO faqs (question, answer) VALUES
('How do I book a cab?', 'Simply fill the booking form on our homepage with your pickup, drop, date and time. We will call you to confirm.'),
('How is the fare calculated?', 'Fare = base fare + (fare per km × distance). The exact rate for each cab is shown on the Pricing page.'),
('Can I cancel my booking?', 'Yes, you can cancel free of charge before the ride starts. Just call us at the number given in the Contact page.'),
('Are your drivers verified?', 'Absolutely. All our drivers are background-verified, licensed and trained for customer courtesy.');

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT '',
    phone VARCHAR(20) DEFAULT '',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
