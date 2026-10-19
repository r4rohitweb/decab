-- Homepage sections upgrade (run on existing decab_db)
USE decab_db;

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
