-- Cab Booking Website Database
CREATE DATABASE IF NOT EXISTS decab_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE decab_db;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$1B./UFKimy4dBc8xEE./o.jP1yfwSY8INEOE77g4awpmNdHE/7AVG');

CREATE TABLE IF NOT EXISTS cabs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    model VARCHAR(100) DEFAULT '',
    plate_number VARCHAR(30) DEFAULT '',
    driver_name VARCHAR(100) DEFAULT '',
    driver_phone VARCHAR(30) DEFAULT '',
    seats INT DEFAULT 4,
    fare_per_km DECIMAL(10,2) NOT NULL DEFAULT 12.00,
    base_fare DECIMAL(10,2) NOT NULL DEFAULT 50.00,
    image VARCHAR(255) DEFAULT '',
    status ENUM('available','busy') DEFAULT 'available'
);

-- Your single cab (add more any time from admin panel)
INSERT INTO cabs (name, model, plate_number, driver_name, driver_phone, seats, fare_per_km, base_fare)
VALUES ('DeCab Sedan', 'Swift Dzire', 'KA-01-AB-1234', 'Ramesh Kumar', '9876543210', 4, 12.00, 50.00);

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
('about_short', 'DeCab is your trusted local taxi partner. One call, one tap — and a clean cab with a friendly driver is on the way. We focus on safety, punctuality and honest pricing.');

CREATE TABLE IF NOT EXISTS pages (
    slug VARCHAR(50) PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    content TEXT
);

INSERT INTO pages (slug, title, content) VALUES
('about', 'About Us', '<p>Welcome to <b>DeCab Taxi Services</b> — your trusted travel partner in the city. We have been serving customers with clean cabs, professional drivers and transparent pricing.</p><p>Our mission is simple: make every ride safe, comfortable and on time. Whether it is a daily office commute, an airport drop or an outstation trip, our drivers know every corner of the city.</p><h3>Why choose us?</h3><ul><li>Clean, well-maintained cabs</li><li>Verified and experienced drivers</li><li>Transparent fares — no hidden charges</li><li>24x7 customer support</li></ul>');

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

CREATE TABLE IF NOT EXISTS stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    svalue VARCHAR(50) NOT NULL,
    label VARCHAR(100) NOT NULL,
    sort INT DEFAULT 0
);

INSERT INTO stats (svalue, label, sort) VALUES
('12,000+', 'Rides Completed', 1),
('8,500+', 'Happy Customers', 2),
('8+', 'Years of Service', 3),
('24x7', 'Support Available', 4);

CREATE TABLE IF NOT EXISTS steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    step_no INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    sort INT DEFAULT 0
);

INSERT INTO steps (step_no, title, description, sort) VALUES
(1, 'Book Online', 'Fill the simple booking form with your pickup, drop, date and time.', 1),
(2, 'We Confirm', 'Our team calls you within minutes to confirm your ride and fare.', 2),
(3, 'Enjoy the Ride', 'Your driver arrives on time in a clean, comfortable cab.', 3),
(4, 'Easy Payment', 'Pay by cash, UPI or card after your comfortable journey.', 4);

CREATE TABLE IF NOT EXISTS features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    icon VARCHAR(10) DEFAULT '⭐',
    title VARCHAR(100) NOT NULL,
    description TEXT,
    sort INT DEFAULT 0
);

INSERT INTO features (icon, title, description, sort) VALUES
('🛡️', 'Safety First', 'Verified drivers, GPS-tracked cabs and 24x7 support for a worry-free ride.', 1),
('🚗', 'Clean & Comfortable', 'Every cab is cleaned and sanitised before every trip.', 2),
('💰', 'Honest Pricing', 'Transparent fares with no hidden charges — pay exactly what we quote.', 3),
('⏰', 'Always On Time', 'We value your time. Your cab arrives when we promise it will.', 4),
('📱', 'Easy Booking', 'Book in less than a minute through our simple online form.', 5),
('🧑‍✈️', 'Expert Drivers', 'Polite, experienced drivers who know every route in the city.', 6);

CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100) DEFAULT '',
    rating INT DEFAULT 5,
    review TEXT,
    sort INT DEFAULT 0
);

INSERT INTO testimonials (name, city, rating, review, sort) VALUES
('Priya Sharma', 'Bengaluru', 5, 'Booked an airport drop at 4 AM and the cab arrived 10 minutes early. Very professional service!', 1),
('Rahul Verma', 'Bengaluru', 5, 'Honest pricing and a very polite driver. DeCab is my go-to taxi service now.', 2),
('Anita Desai', 'Bengaluru', 4, 'Clean car, smooth ride and easy online booking. Highly recommended for outstation trips.', 3);

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cab_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(150) DEFAULT '',
    customer_phone VARCHAR(20) NOT NULL,
    trip_type VARCHAR(50) DEFAULT 'Local ride',
    passenger_count INT DEFAULT 1,
    pickup VARCHAR(255) NOT NULL,
    drop_location VARCHAR(255) NOT NULL,
    ride_date DATE DEFAULT NULL,
    ride_time TIME DEFAULT NULL,
    distance_km DECIMAL(10,2) DEFAULT 0,
    fare DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cab_id) REFERENCES cabs(id) ON DELETE CASCADE
);
