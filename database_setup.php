-- Drop existing database if exists and create a new one
DROP DATABASE IF EXISTS blood_bank_db;
CREATE DATABASE blood_bank_db;
USE blood_bank_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    nid_number VARCHAR(50) NOT NULL UNIQUE,
    address TEXT NOT NULL,
    user_type ENUM('user', 'admin') NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Roles table
CREATE TABLE IF NOT EXISTS user_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    is_donor BOOLEAN DEFAULT FALSE,
    is_recipient BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Blood Stock table
CREATE TABLE IF NOT EXISTS blood_stock (
    stock_id INT AUTO_INCREMENT PRIMARY KEY,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    quantity_ml INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Donor Profiles table
CREATE TABLE IF NOT EXISTS donor_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    last_donation_date DATE,
    medical_info TEXT,
    smoker ENUM('yes', 'no') NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Donation Requests table
CREATE TABLE IF NOT EXISTS donation_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    quantity_ml INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Blood Requests table (for recipients)
CREATE TABLE IF NOT EXISTS blood_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    donor_id INT,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    quantity_ml INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (recipient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Posts table
CREATE TABLE IF NOT EXISTS posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    post_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Comments table
CREATE TABLE IF NOT EXISTS comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    comment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Likes table
CREATE TABLE IF NOT EXISTS likes (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT,
    comment_id INT,
    reaction_type ENUM('like', 'love', 'care', 'haha', 'wow', 'sad', 'angry') DEFAULT 'like',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    message_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    notification_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);



CREATE TABLE recipient_profiles (
    profile_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    medical_history TEXT DEFAULT NULL,
    preferred_hospital VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (profile_id),
    UNIQUE KEY (user_id)
);

-- Profile Pictures table
CREATE TABLE IF NOT EXISTS profile_pictures (
    picture_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Badges table
CREATE TABLE IF NOT EXISTS badges (
    badge_id INT AUTO_INCREMENT PRIMARY KEY,
    badge_name VARCHAR(50) NOT NULL,
    badge_description TEXT NOT NULL,
    badge_icon VARCHAR(50) NOT NULL,
    badge_criteria VARCHAR(255) NOT NULL
);

-- User Badges table
CREATE TABLE IF NOT EXISTS user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    earned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(badge_id) ON DELETE CASCADE
);

-- Donation Logs table
CREATE TABLE IF NOT EXISTS donation_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    quantity_ml INT NOT NULL,
    donation_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create the dismissed_alerts table
CREATE TABLE IF NOT EXISTS dismissed_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    alert_id INT NOT NULL,
    dismissal_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, alert_id)
);



-- Blood Request Logs table
CREATE TABLE IF NOT EXISTS blood_request_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    donor_id INT,
    blood_group VARCHAR(5) NOT NULL,
    quantity_ml INT NOT NULL,
    request_date DATE NOT NULL,
    FOREIGN KEY (recipient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Emergency Logs table
CREATE TABLE IF NOT EXISTS emergency_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    location VARCHAR(255) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    details TEXT NOT NULL,
    emergency_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Insert initial blood stock records
INSERT INTO blood_stock (blood_group, quantity_ml) VALUES 
('A+', 1000),
('A-', 800),
('B+', 1200),
('B-', 500),
('AB+', 300),
('AB-', 250),
('O+', 1500),
('O-', 700);

-- Create admin user
INSERT INTO users (full_name, email, password, contact_number, nid_number, address, user_type) 
VALUES ('Admin User', 'admin@bloodbank.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01234567890', 'ADMIN123', 'Blood Bank HQ', 'admin');

-- Insert default badges
INSERT INTO badges (badge_name, badge_description, badge_icon, badge_criteria) VALUES
('First Donation', 'Completed your first blood donation', 'fas fa-award text-primary', 'donations_count >= 1'),
('Regular Donor', 'Donated blood at least 3 times', 'fas fa-medal text-success', 'donations_count >= 3'),
('Super Donor', 'Donated blood at least 10 times', 'fas fa-trophy text-warning', 'donations_count >= 10'),
('Life Saver', 'Responded to an emergency blood request', 'fas fa-heart text-danger', 'emergency_responses >= 1'),
('Blood Type Expert', 'Completed donor profile with blood type information', 'fas fa-tint text-danger', 'has_donor_profile = 1');

-- Sample users (passwords = "password")
INSERT INTO users (full_name, email, password, contact_number, nid_number, address, user_type) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01711111111', 'NID123456789', 'Dhaka, Bangladesh', 'user'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01722222222', 'NID987654321', 'Chittagong, Bangladesh', 'user'),
('Bob Johnson', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01733333333', 'NID111222333', 'Sylhet, Bangladesh', 'user'),
('Alice Brown', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01744444444', 'NID444555666', 'Rajshahi, Bangladesh', 'user');

-- Sample user roles
INSERT INTO user_roles (user_id, is_donor, is_recipient) VALUES
(2, TRUE, TRUE),  -- John is both donor and recipient
(3, TRUE, FALSE), -- Jane is only donor
(4, FALSE, TRUE), -- Bob is only recipient
(5, TRUE, TRUE);  -- Alice is both donor and recipient

-- Sample donor profiles
INSERT INTO donor_profiles (user_id, blood_group, last_donation_date, medical_info, smoker, gender, is_available) VALUES
(2, 'A+', '2023-01-15', 'No major health issues', 'no', 'male', TRUE),
(3, 'B-', '2023-03-20', 'Mild allergies to pollen', 'no', 'female', TRUE),
(5, 'O+', '2023-02-10', 'Vaccinated for COVID-19', 'no', 'female', TRUE);

-- Sample donation logs
INSERT INTO donation_logs (user_id, blood_group, quantity_ml, donation_date) VALUES
(2, 'A+', 450, '2023-01-15'),
(3, 'B-', 400, '2023-03-20'),
(5, 'O+', 450, '2023-02-10'),
(2, 'A+', 450, '2022-09-10'),
(3, 'B-', 400, '2022-10-15'),
(5, 'O+', 450, '2022-11-05');

-- Sample posts
INSERT INTO posts (user_id, content, post_date) VALUES
(2, 'I just donated blood today! It feels great to be able to help others. If you are in good health, consider donating blood too!', '2023-04-10 09:15:00'),
(3, 'Does anyone know where I can donate blood in Chittagong? Looking for a reliable blood bank.', '2023-04-12 14:30:00'),
(4, 'Urgently need B+ blood for my father who is undergoing surgery tomorrow. Please contact me if you can help.', '2023-04-15 18:45:00'),
(5, 'Sharing my experience: Donating blood is quick, easy, and painless. The staff at the blood bank were very professional.', '2023-04-18 11:20:00');

-- Sample comments
INSERT INTO comments (post_id, user_id, content, comment_date) VALUES
(1, 3, 'That\'s awesome! I\'m planning to donate next week.', '2023-04-10 10:30:00'),
(1, 5, 'Thank you for your contribution! Every donation counts.', '2023-04-10 11:45:00'),
(3, 2, 'I\'m A+ but will share your post. Hope you find a donor soon!', '2023-04-15 19:20:00'),
(3, 5, 'I\'m O+ and can donate if needed. Please message me for details.', '2023-04-15 20:10:00'),
(4, 3, 'I had the same experience. The staff was very friendly.', '2023-04-18 13:25:00');

-- Sample notifications for users
INSERT INTO notifications (user_id, content, is_read, notification_date) VALUES
(2, 'Your donation request has been approved. Please visit our center to donate blood.', FALSE, '2023-04-09 15:30:00'),
(3, 'Thank you for your recent blood donation!', TRUE, '2023-03-20 16:45:00'),
(4, 'Bob Johnson is looking for B+ blood for emergency surgery. Can you help?', FALSE, '2023-04-15 19:00:00'),
(5, 'Your blood request has been approved. Please visit our center to collect your blood.', TRUE, '2023-02-08 10:15:00');

-- Award badges to users
INSERT INTO user_badges (user_id, badge_id) VALUES
(2, 1), -- John: First Donation badge
(3, 1), -- Jane: First Donation badge
(5, 1), -- Alice: First Donation badge
(5, 2); -- Alice: Regular Donor badge (assuming multiple donations)