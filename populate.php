-- First, clear existing sample data (except for blood_stock and admin user)
DELETE FROM user_badges;
DELETE FROM badges WHERE badge_id > 5;
DELETE FROM emergency_logs;
DELETE FROM blood_request_logs;
DELETE FROM donation_logs;
DELETE FROM dismissed_alerts;
DELETE FROM notifications;
DELETE FROM messages;
DELETE FROM likes;
DELETE FROM comments;
DELETE FROM posts;
DELETE FROM blood_requests;
DELETE FROM donation_requests;
DELETE FROM donor_profiles;
DELETE FROM user_roles;
DELETE FROM profile_pictures;
DELETE FROM users WHERE user_id > 1;

-- Reset auto-increment counters to ensure we start with ID 2 for new users
ALTER TABLE users AUTO_INCREMENT = 2;

-- Insert 35 users with diversified data
INSERT INTO users (full_name, email, password, contact_number, nid_number, address, user_type) VALUES
('Nashid A Sikder', 'nashid.sikder@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801711234567', '1987654321001', 'Chandrima Model Town(Future), Dhaka', 'user'),
('Ramisa Hasan Somprity', 'ramisa.somprity@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801712345678', '1987654321002', 'Paribagh(Opposite of Intercontinental), Dhaka', 'user'),
('Iftikharul Islam Ifti', 'ifti.islam@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801723456789', '1987654321003', 'Katasur, Dhaka', 'user'),
('Armeen Ahmed Amin', 'armeen.amin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801734567890', '1987654321004', 'Uttara Sector-4, Dhaka', 'user'),
('Shontolee Marium', 'shontolee.marium@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801745678901', '1987654321005', 'Banani Block-C, Dhaka', 'user'),
('Zead Raihan', 'zead.raihan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801756789012', '1987654321006', 'Mohammadpur, Dhaka', 'user'),
('Shahrin Nuha', 'shahrin.nuha@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801767890123', '1987654321007', 'Motijheel, Dhaka', 'user'),
('Shourav Ansary', 'shourav.ansary@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801778901234', '1987654321008', 'Wari, Dhaka', 'user'),
('Jamil Ahmed', 'jamil.ahmed@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801789012345', '1987654321009', 'Khilgaon, Dhaka', 'user'),
('Shoily Shokuntola', 'shoily.shokuntola@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801790123456', '1987654321010', 'Bashundhara R/A, Dhaka', 'user'),
('Rayan Tamim Ahona', 'rayan.ahona@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801801234567', '1987654321011', 'Tejgaon, Dhaka', 'admin'),
('Ramisa Khandaker Raimy', 'ramisa.raimy@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801812345678', '1987654321012', 'Badda, Dhaka', 'user'),
('Yeashfi Ahmed', 'yeashfi.ahmed@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801823456789', '1987654321013', 'Rampura, Dhaka', 'user'),
('Pimpim', 'pimpim@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801834567890', '1987654321014', 'Malibagh, Dhaka', 'user'),
('Ifrad Shaoky', 'ifrad.shaoky@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801845678901', '1987654321015', 'Shantinagar, Dhaka', 'user'),
('Marshia Nujhat', 'marshia.nujhat@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801856789012', '1987654321016', 'Lalmatia, Dhaka', 'user'),
('Najeefa Maam', 'najeefa.maam@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801867890123', '1987654321017', 'Eskaton, Dhaka', 'user'),
('Imran Zahid', 'imran.zahid@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801878901234', '1987654321018', 'Niketon, Dhaka', 'user'),
('Sumaiya Islam Samia', 'sumaiya.samia@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801889012345', '1987654321019', 'Baridhara, Dhaka', 'user'),
('Sanjana Prionty', 'sanjana.prionty@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801890123456', '1987654321020', 'Banasree, Dhaka', 'user'),
('Nishanoor Alam Chowdhury', 'nishanoor.chowdhury@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801901234567', '1987654321021', 'Mohakhali, Dhaka', 'user'),
('Zubia Saqib', 'zubia.saqib@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801912345678', '1987654321022', 'Puran Dhaka, Dhaka', 'user'),
('Lamia Khan', 'lamia.khan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801923456789', '1987654321023', 'Farmgate, Dhaka', 'user'),
('Fahim Alvee', 'fahim.alvee@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801934567890', '1987654321024', 'Adabor, Dhaka', 'user'),
('Anika Alamgir', 'anika.alamgir@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801945678901', '1987654321025', 'Shahjahanpur, Dhaka', 'user'),
('Rafa Al Shahriar', 'rafa.shahriar@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801956789012', '1987654321026', 'Jatrabari, Dhaka', 'user'),
('Ruwad Naswan', 'ruwad.naswan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801967890123', '1987654321027', 'Shyamoli, Dhaka', 'user'),
('Mashiur Rahman Khan', 'mashiur.khan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801978901234', '1987654321028', 'Panthapath, Dhaka', 'user'),
('Zawad Al Munawar', 'zawad.munawar@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801989012345', '1987654321029', 'Kafrul, Dhaka', 'user'),
('Afifa Ahmed', 'afifa.ahmed@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8801990123456', '1987654321030', 'Mugda, Dhaka', 'user'),
('Mahjabeen Tamanna Abed', 'mahjabeen.abed@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8802001234567', '1987654321031', 'Cantonment, Dhaka', 'user'),
('Swakkhar Swatabda', 'swakkhar.swatabda@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8802012345678', '1987654321032', 'Kalabagan, Dhaka', 'user'),
('Kaniz Salma Now', 'kaniz.now@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8802023456789', '1987654321033', 'Mohammadpur, Dhaka', 'user'),
('Shahriar Sajib', 'shahriar.sajib@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8802034567890', '1987654321034', 'Agargaon, Dhaka', 'user'),
('Doula Rahman', 'doula.rahman@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8802045678901', '1987654321035', 'Nikunja, Dhaka', 'user'),
('Professor Mohammad Yunus', 'mohammad.yunus@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+8802056789012', '1987654321036', 'Mirpur DOHS, Dhaka', 'admin');

-- NOW we can assign user roles (mix of donors and recipients)
INSERT INTO user_roles (user_id, is_donor, is_recipient) VALUES
(2, TRUE, FALSE),   -- Nashid A Sikder (donor only)
(3, TRUE, TRUE),    -- Ramisa Hasan Somprity (both)
(4, TRUE, TRUE),    -- Iftikharul Islam Ifti (both)
(5, TRUE, FALSE),   -- Armeen Ahmed Amin (donor only)
(6, FALSE, TRUE),   -- Shontolee Marium (recipient only)
(7, TRUE, FALSE),   -- Zead Raihan (donor only)
(8, FALSE, TRUE),   -- Shahrin Nuha (recipient only)
(9, TRUE, TRUE),    -- Shourav Ansary (both)
(10, FALSE, TRUE),  -- Jamil Ahmed (recipient only)
(11, TRUE, FALSE),  -- Shoily Shokuntola (donor only)
(12, TRUE, TRUE),   -- Rayan Tamim Ahona (both, admin)
(13, FALSE, TRUE),  -- Ramisa Khandaker Raimy (recipient only)
(14, TRUE, FALSE),  -- Yeashfi Ahmed (donor only)
(15, FALSE, TRUE),  -- Pimpim (recipient only)
(16, TRUE, TRUE),   -- Ifrad Shaoky (both)
(17, TRUE, FALSE),  -- Marshia Nujhat (donor only)
(18, FALSE, TRUE),  -- Najeefa Maam (recipient only)
(19, TRUE, FALSE),  -- Imran Zahid (donor only)
(20, TRUE, TRUE),   -- Sumaiya Islam Samia (both)
(21, FALSE, TRUE),  -- Sanjana Prionty (recipient only)
(22, TRUE, FALSE),  -- Nishanoor Alam Chowdhury (donor only)
(23, FALSE, TRUE),  -- Zubia Saqib (recipient only)
(24, TRUE, TRUE),   -- Lamia Khan (both)
(25, TRUE, FALSE),  -- Fahim Alvee (donor only)
(26, FALSE, TRUE),  -- Anika Alamgir (recipient only)
(27, TRUE, FALSE),  -- Rafa Al Shahriar (donor only)
(28, TRUE, TRUE),   -- Ruwad Naswan (both)
(29, FALSE, TRUE),  -- Mashiur Rahman Khan (recipient only)
(30, TRUE, FALSE),  -- Zawad Al Munawar (donor only)
(31, FALSE, TRUE),  -- Afifa Ahmed (recipient only)
(32, TRUE, TRUE),   -- Mahjabeen Tamanna Abed (both)
(33, TRUE, FALSE),  -- Swakkhar Swatabda (donor only)
(34, FALSE, TRUE),  -- Kaniz Salma Now (recipient only)
(35, TRUE, TRUE),   -- Shahriar Sajib (both)
(36, TRUE, FALSE),  -- Doula Rahman (donor only)
(37, TRUE, TRUE);   -- Professor Mohammad Yunus (both, admin)

-- Create donor profiles 
INSERT INTO donor_profiles (user_id, blood_group, last_donation_date, medical_info, smoker, gender, is_available) VALUES
(2, 'O+', '2023-11-15', 'Emotional cries often', 'no', 'male', TRUE),           -- Nashid A Sikder (O+)
(3, 'B+', '2023-10-20', 'Healthy and Tall but sometimes take Iron supplements', 'no', 'female', TRUE), -- Ramisa Hasan Somprity (B+)
(4, 'B+', '2023-12-05', 'Cute but hits the gym regularly and takes steriods sometimes', 'no', 'male', TRUE), -- Iftikharul Islam Ifti (B+)
(5, 'O+', '2024-01-10', 'Regular blood donor for 5 years', 'no', 'male', TRUE),         -- Armeen Ahmed Amin (O+)
(6, 'O+', '2024-01-10', 'Architect', 'no', 'Female', TRUE),        
(7, 'A+', '2023-09-25', 'No medical issues', 'yes', 'male', TRUE),                     -- Zead Raihan (A+)
(9, 'AB+', '2024-02-14', 'High blood pressure, controlled with medication', 'no', 'male', TRUE), -- Shourav Ansary (AB+)
(11, 'A-', '2023-11-30', 'No known medical issues', 'no', 'female', TRUE),               -- Shoily Shokuntola (A-)
(12, 'O-', '2024-03-02', 'Asthma, well-controlled', 'no', 'male', TRUE),                -- Rayan Tamim Ahona (O-)
(14, 'B-', '2023-10-10', 'No medical issues', 'no', 'male', TRUE),                      -- Yeashfi Ahmed (B-)
(16, 'AB-', '2024-01-20', 'Mild seasonal allergies', 'no', 'male', TRUE),               -- Ifrad Shaoky (AB-)
(17, 'A+', '2023-12-15', 'Healthy, regular exercise', 'no', 'female', TRUE),            -- Marshia Nujhat (A+)
(19, 'O+', '2024-02-05', 'Controlled hypertension', 'no', 'male', TRUE),                -- Imran Zahid (O+)
(20, 'B+', '2023-11-12', 'No known medical issues', 'no', 'female', TRUE),              -- Sumaiya Islam Samia (B+)
(22, 'AB+', '2024-03-10', 'Normal health, no issues', 'no', 'male', TRUE),              -- Nishanoor Alam Chowdhury (AB+)
(24, 'A+', '2023-09-15', 'Taking iron supplements', 'no', 'female', TRUE), -- Lamia Khan (A+)
(25, 'O-', '2024-01-30', 'No medical issues', 'yes', 'male', TRUE),                     -- Fahim Alvee (O-)
(27, 'B-', '2023-12-20', 'No medical issues', 'no', 'male', TRUE),                      -- Rafa Al Shahriar (B-)
(28, 'A-', '2024-02-25', 'Vitamin B12 deficiency, taking supplements', 'no', 'male', TRUE), -- Ruwad Naswan (A-)
(30, 'O+', '2023-10-30', 'No known medical issues', 'no', 'male', TRUE),                -- Zawad Al Munawar (O+)
(32, 'AB-', '2024-03-15', 'No medical issues', 'no', 'female', TRUE),                   -- Mahjabeen Tamanna Abed (AB-)
(33, 'B+', '2023-12-10', 'Regular blood donor', 'no', 'male', TRUE),                    -- Swakkhar Swatabda (B+)
(35, 'A+', '2024-01-05', 'No medical issues', 'no', 'male', TRUE),                      -- Shahriar Sajib (A+)
(36, 'O+', '2023-11-05', 'No known medical issues', 'no', 'male', TRUE),                -- Doula Rahman (O+)
(37, 'AB+', '2024-02-20', 'Controlled diabetes', 'no', 'male', TRUE);                   -- Professor Mohammad Yunus (AB+)

-- Now add the remaining tables in the correct order
-- Record donation logs with varied dates and quantities
INSERT INTO donation_logs (user_id, blood_group, quantity_ml, donation_date) VALUES
-- Multiple donations for some users
(2, 'O+', 450, '2023-11-15'),    -- Nashid
(2, 'O+', 450, '2023-08-10'),
(2, 'O+', 450, '2023-05-05'),
(3, 'B+', 400, '2023-10-20'),    -- Ramisa
(3, 'B+', 400, '2023-06-15'),
(4, 'B+', 450, '2023-12-05'),    -- Ifti
(4, 'B+', 450, '2023-09-02'),
(4, 'B+', 450, '2023-06-01'),
(5, 'O+', 450, '2024-01-10'),    -- Armeen
(5, 'O+', 450, '2023-10-05'),
(7, 'A+', 400, '2023-09-25'),    -- Zead
(9, 'AB+', 350, '2024-02-14'),   -- Shourav
(9, 'AB+', 350, '2023-11-10'),
(11, 'A-', 400, '2023-11-30'),   -- Shoily
(12, 'O-', 450, '2024-03-02'),   -- Rayan
(12, 'O-', 450, '2023-12-01'),
(12, 'O-', 450, '2023-09-05'),
(12, 'O-', 450, '2023-06-10'),
(14, 'B-', 400, '2023-10-10'),   -- Yeashfi
(16, 'AB-', 350, '2024-01-20'),  -- Ifrad
(17, 'A+', 400, '2023-12-15'),   -- Marshia
(17, 'A+', 400, '2023-09-15'),
(19, 'O+', 450, '2024-02-05'),   -- Imran
(19, 'O+', 450, '2023-11-01'),
(20, 'B+', 400, '2023-11-12'),   -- Sumaiya
(22, 'AB+', 350, '2024-03-10'),  -- Nishanoor
(24, 'A+', 400, '2023-09-15'),   -- Lamia
(25, 'O-', 450, '2024-01-30'),   -- Fahim
(25, 'O-', 450, '2023-10-28'),
(27, 'B-', 400, '2023-12-20'),   -- Rafa
(28, 'A-', 400, '2024-02-25'),   -- Ruwad
(30, 'O+', 450, '2023-10-30'),   -- Zawad
(32, 'AB-', 350, '2024-03-15'),  -- Mahjabeen
(33, 'B+', 400, '2023-12-10'),   -- Swakkhar
(33, 'B+', 400, '2023-09-05'),
(35, 'A+', 400, '2024-01-05'),   -- Shahriar
(36, 'O+', 450, '2023-11-05'),   -- Doula
(37, 'AB+', 350, '2024-02-20');  -- Professor Yunus

-- Create blood requests with varied statuses
INSERT INTO blood_requests (recipient_id, donor_id, blood_group, quantity_ml, request_date, status) VALUES
(6, 2, 'O+', 450, '2024-03-01', 'approved'),           -- Shontolee requesting from Nashid
(8, 3, 'B+', 400, '2024-02-15', 'approved'),           -- Shahrin requesting from Ramisa
(10, 9, 'AB+', 350, '2024-03-05', 'approved'),         -- Jamil requesting from Shourav
(13, 4, 'B+', 450, '2024-03-10', 'pending'),           -- Ramisa Raimy requesting from Ifti
(15, 5, 'O+', 450, '2024-02-20', 'approved'),          -- Pimpim requesting from Armeen
(18, 17, 'A+', 400, '2024-03-08', 'pending'),          -- Najeefa requesting from Marshia
(21, 20, 'B+', 400, '2024-02-25', 'approved'),         -- Sanjana requesting from Sumaiya
(23, 12, 'O-', 450, '2024-03-12', 'pending'),          -- Zubia requesting from Rayan
(26, 25, 'O-', 450, '2024-03-15', 'pending'),          -- Anika requesting from Fahim
(29, 28, 'A-', 400, '2024-02-28', 'approved'),         -- Mashiur requesting from Ruwad
(31, 32, 'AB-', 350, '2024-03-18', 'pending'),         -- Afifa requesting from Mahjabeen
(34, 33, 'B+', 400, '2024-02-18', 'approved');         -- Kaniz requesting from Swakkhar

-- Create donation requests
INSERT INTO donation_requests (user_id, blood_group, quantity_ml, request_date, status) VALUES
(2, 'O+', 450, '2024-03-20', 'pending'),     -- Nashid
(3, 'B+', 400, '2024-03-18', 'approved'),    -- Ramisa
(4, 'B+', 450, '2024-03-15', 'approved'),    -- Ifti
(5, 'O+', 450, '2024-03-22', 'pending'),     -- Armeen
(7, 'A+', 400, '2024-03-10', 'approved'),    -- Zead
(9, 'AB+', 350, '2024-03-19', 'pending'),    -- Shourav
(11, 'A-', 400, '2024-03-12', 'approved'),   -- Shoily
(12, 'O-', 450, '2024-03-21', 'pending'),    -- Rayan
(14, 'B-', 400, '2024-03-11', 'approved'),   -- Yeashfi
(16, 'AB-', 350, '2024-03-17', 'pending'),   -- Ifrad
(17, 'A+', 400, '2024-03-14', 'approved'),   -- Marshia
(19, 'O+', 450, '2024-03-16', 'pending'),    -- Imran
(20, 'B+', 400, '2024-03-13', 'approved');   -- Sumaiya

-- Reset the auto-increment for posts table (if needed)
ALTER TABLE posts AUTO_INCREMENT = 1;

-- Create posts with varied timestamps
INSERT INTO posts (user_id, content, post_date) VALUES
(2, 'Just donated blood today! Feeling great knowing I might help save someone\'s life. #BloodDonation #LifeSaver', '2024-03-15 09:30:00'),
(3, 'Blood donation is a simple act that can make a huge difference. I encourage everyone who can to donate! #DonateBlood', '2024-03-10 14:15:00'),
(5, 'Did you know that one blood donation can save up to three lives? I\'m proud to be a regular donor. #BloodDonationFacts', '2024-03-08 11:45:00'),
(12, 'As a blood bank administrator, I\'ve seen firsthand how blood donations save lives. Thank you to all our donors! #ThankYouDonors', '2024-03-18 10:20:00'),
(6, 'Received blood today for my surgery. Eternally grateful to the anonymous donor who made this possible. #ForeverThankful', '2024-03-14 16:30:00'),
(9, 'Organized a blood donation camp at my university today. Great turnout! #BloodDonationCamp #CommunityService', '2024-03-12 13:45:00'),
(4, 'Remember, your blood type matters! Different blood types can donate to different recipients. Check our blood bank app for compatibility info. #BloodTypeFacts', '2024-03-16 15:10:00'),
(8, 'Looking for O- blood donors for my father\'s upcoming surgery. Please contact me if you can help. #UrgentNeed #ONegative', '2024-03-19 17:25:00'),
(11, 'Donated blood for the first time today. The process was quick and painless! Don\'t be afraid to donate. #FirstTimeDonor', '2024-03-11 12:30:00'),
(19, 'Remember to eat well and stay hydrated before donating blood! #DonationTips #HealthyDonor', '2024-03-17 14:50:00'),
(37, 'As a medical professional, I can confirm that blood donations are crucial for emergency services. Your donation matters. #SaveLives', '2024-03-20 09:15:00'),
(27, 'Hosting a blood donation awareness session this weekend. Join us to learn more! #BloodDonationAwareness', '2024-03-13 18:30:00'),
(24, 'My sister needed blood last year, and donors saved her life. Now I donate regularly to pay it forward. #GratefulForever', '2024-03-09 11:20:00'),
(7, 'Did you know that after donating blood, your body replaces all the red blood cells within 4-6 weeks? #DidYouKnow #BloodFacts', '2024-03-07 15:40:00'),
(32, 'Thank you to all the medical staff who make blood donation a comfortable experience! #AppreciationPost', '2024-03-18 13:10:00');


-- Create comments on posts (only after posts are successfully inserted)
INSERT INTO comments (post_id, user_id, content, comment_date) VALUES
(1, 6, 'Thank you for donating! People like you saved my life during my recent surgery.', '2024-03-15 10:15:00'),
(1, 13, 'You\'re a hero! I\'m inspired to donate soon too.', '2024-03-15 11:30:00'),
(2, 8, 'Completely agree! It\'s such a simple way to make a huge impact.', '2024-03-10 15:20:00'),
(2, 5, 'I donate every 3 months. It feels great to help others.', '2024-03-10 16:45:00'),
(3, 10, 'Wow, I didn\'t know that! Thanks for sharing this information.', '2024-03-08 12:30:00'),
(3, 23, 'This is why I try to donate regularly. Every donation counts!', '2024-03-08 13:15:00'),
(4, 7, 'Thank you for your work at the blood bank! You all do amazing work.', '2024-03-18 11:05:00'),
(4, 29, 'I\'ll be coming in to donate this weekend. See you there!', '2024-03-18 12:30:00'),
(5, 3, 'So glad to hear you received the blood you needed. Wishing you a speedy recovery!', '2024-03-14 17:15:00'),
(5, 11, 'This is why we donate. Wishing you well in your recovery.', '2024-03-14 18:00:00'),
(6, 12, 'Great initiative! We need more blood donation camps like this.', '2024-03-12 14:30:00'),
(6, 25, 'I was there! Such a well-organized event. Proud to have donated.', '2024-03-12 15:10:00'),
(7, 21, 'Very informative! I never knew about the compatibility between different blood types.', '2024-03-16 16:00:00'),
(7, 9, 'This is why education about blood donation is so important. Great post!', '2024-03-16 17:20:00'),
(8, 2, 'I\'m O negative and just messaged you. Hope I can help!', '2024-03-19 18:00:00'),
(8, 25, 'I\'ll share this with my network to reach more potential donors.', '2024-03-19 18:45:00'),
(9, 4, 'Congratulations on your first donation! Hope to see you become a regular donor.', '2024-03-11 13:15:00'),
(9, 20, 'The first time is always the hardest. Well done!', '2024-03-11 14:00:00'),
(10, 15, 'Great tips! I always forget to drink enough water before donating.', '2024-03-17 15:30:00'),
(10, 22, 'I also recommend getting a good night\'s sleep before donation day!', '2024-03-17 16:15:00');


-- Create messages between users
INSERT INTO messages (sender_id, receiver_id, content, message_date, is_read) VALUES
(2, 8, 'Hey, I saw your post about needing O- blood. I\'m O+ but I can help spread the word.', '2024-03-19 19:00:00', TRUE),
(8, 2, 'Thank you so much! We\'re really desperate to find donors.', '2024-03-19 19:15:00', TRUE),
(2, 8, 'No problem. I\'ve shared it with my network. Hope you find donors soon.', '2024-03-19 19:30:00', TRUE),
(3, 6, 'Hi there! Hope you\'re recovering well after your surgery.', '2024-03-15 10:45:00', TRUE),
(6, 3, 'Yes, I am. Thank you for your concern! The blood transfusion was really helpful.', '2024-03-15 11:00:00', TRUE),
(3, 6, 'That\'s great to hear! Take care and get well soon.', '2024-03-15 11:15:00', TRUE),
(4, 9, 'Hello! I saw you organized a blood donation camp. I\'d like to help with the next one.', '2024-03-13 09:30:00', TRUE),
(9, 4, 'That would be fantastic! We\'re planning another one next month. Can we meet to discuss?', '2024-03-13 10:00:00', TRUE),
(4, 9, 'Absolutely! How about next week?', '2024-03-13 10:15:00', TRUE),
(9, 4, 'Perfect! Tuesday at 3 PM at the university café?', '2024-03-13 10:30:00', TRUE),
(4, 9, 'Works for me. See you then!', '2024-03-13 10:45:00', TRUE),
(12, 5, 'Hi Armeen, thank you for being a regular donor at our blood bank.', '2024-03-18 14:00:00', TRUE),
(5, 12, 'Happy to help! It feels good to know I\'m making a difference.', '2024-03-18 14:30:00', TRUE),
(12, 5, 'You definitely are! We need more donors like you.', '2024-03-18 15:00:00', TRUE),
(5, 12, 'I\'ll try to encourage more friends to donate.', '2024-03-18 15:30:00', TRUE),
(7, 11, 'Hi, I saw you donated for the first time. How was your experience?', '2024-03-12 10:00:00', TRUE),
(11, 7, 'It was great! Much easier than I expected. Will definitely do it again.', '2024-03-12 10:30:00', TRUE),
(7, 11, 'That\'s wonderful to hear! The first time is always the hardest.', '2024-03-12 11:00:00', TRUE),
(11, 7, 'Yes, I was nervous but the staff made me feel comfortable.', '2024-03-12 11:30:00', TRUE),
(37, 12, 'Hello Rayan, I\'d like to discuss a potential blood donation awareness program at the university.', '2024-03-21 09:00:00', FALSE),
(12, 37, 'That sounds like a great initiative, Professor Yunus. I\'d be happy to help organize it.', '2024-03-21 09:30:00', TRUE),
(37, 12, 'Excellent! Let\'s schedule a meeting next week to discuss the details.', '2024-03-21 10:00:00', FALSE);

-- Create notifications for users
INSERT INTO notifications (user_id, content, is_read, notification_date) VALUES
(2, 'Your blood donation has helped save a life! Thank you for your contribution.', FALSE, '2024-03-16 10:00:00'),
(3, 'Your blood donation request has been approved. Please visit the blood bank at your convenience.', TRUE, '2024-03-18 15:00:00'),
(4, 'Your blood donation request has been approved. Please visit the blood bank at your convenience.', TRUE, '2024-03-15 16:00:00'),
(5, 'Your recent post about blood donation facts has been liked by 10 people.', FALSE, '2024-03-09 13:00:00'),
(6, 'A donor has responded to your blood request. Check your messages for details.', TRUE, '2024-03-15 11:30:00'),
(7, 'Your blood donation request has been approved. Please visit the blood bank at your convenience.', TRUE, '2024-03-10 17:00:00'),
(8, 'Someone has commented on your urgent blood request post. Check it out!', FALSE, '2024-03-19 18:30:00'),
(9, 'Your blood donation camp post has received 5 new comments.', TRUE, '2024-03-13 09:00:00'),
(10, 'A matching blood donor has been found for your request. Check your messages.', FALSE, '2024-03-20 14:00:00'),
(11, 'Congratulations! You\'ve earned the "First Donation" badge. Check your profile.', TRUE, '2024-03-11 14:00:00'),
(12, 'You have 3 unread messages from potential blood donors.', FALSE, '2024-03-21 10:30:00'),
(15, 'Your blood request has been processed. We\'ll notify you when a donor is found.', TRUE, '2024-03-20 16:00:00'),
(20, 'Thank you for donating blood! Your donation has been added to our records.', FALSE, '2024-03-14 11:00:00'),
(25, 'Your blood donation has been scheduled for tomorrow. Don\'t forget to eat well and stay hydrated!', TRUE, '2024-03-22 09:00:00'),
(31, 'A donor matching your blood type has been found. Check your messages for details.', FALSE, '2024-03-19 15:00:00');

-- Create emergency logs
INSERT INTO emergency_logs (user_id, blood_group, location, contact, details, emergency_date) VALUES
(8, 'O-', 'Dhaka Medical College Hospital', '+8801778901234', 'Father needs urgent blood for surgery tomorrow morning', '2024-03-19 17:00:00'),
(13, 'B+', 'Square Hospital, Panthapath', '+8801812345678', 'Sister needs blood for childbirth complications', '2024-03-20 14:30:00'),
(15, 'AB+', 'United Hospital, Gulshan', '+8801834567890', 'Urgent need for blood after car accident', '2024-03-18 22:15:00'),
(21, 'A+', 'Labaid Hospital, Dhanmondi', '+8801890123456', 'Mother needs blood for emergency surgery', '2024-03-17 18:45:00'),
(26, 'O+', 'Ibn Sina Hospital, Mohammadpur', '+8801945678901', 'Brother needs blood after serious injury', '2024-03-21 08:30:00');

-- Create blood request logs
INSERT INTO blood_request_logs (recipient_id, donor_id, blood_group, quantity_ml, request_date) VALUES
(6, 2, 'O+', 450, '2024-03-01'),
(8, 3, 'B+', 400, '2024-02-15'),
(10, 9, 'AB+', 350, '2024-03-05'),
(15, 5, 'O+', 450, '2024-02-20'),
(21, 20, 'B+', 400, '2024-02-25'),
(29, 28, 'A-', 400, '2024-02-28'),
(34, 33, 'B+', 400, '2024-02-18');



-- Award badges to users
INSERT INTO user_badges (user_id, badge_id) VALUES
-- First Donation badge (badge_id = 1)
(2, 1), (3, 1), (4, 1), (5, 1), (7, 1), (9, 1), (11, 1), (12, 1), (14, 1), (16, 1), (17, 1), (19, 1), (20, 1),
-- Regular Donor badge (badge_id = 2) - for those with 3+ donations
(2, 2), (4, 2), (12, 2),
-- Super Donor badge (badge_id = 3) - not applicable yet
-- Life Saver badge (badge_id = 4) - for those who responded to emergencies
(2, 4), (3, 4), (12, 4), (25, 4), (28, 4),
-- Blood Type Expert badge (badge_id = 5) - for all donors with profiles
(2, 5), (3, 5), (4, 5), (5, 5), (7, 5), (9, 5), (11, 5), (12, 5), (14, 5), (16, 5), (17, 5), (19, 5), (20, 5), 
(22, 5), (24, 5), (25, 5), (27, 5), (28, 5), (30, 5), (32, 5), (33, 5), (35, 5), (36, 5), (37, 5);

-- Create some dismissed alerts
INSERT INTO dismissed_alerts (user_id, alert_id, dismissal_date) VALUES
(2, 1, '2024-03-10 09:30:00'),
(3, 2, '2024-03-11 10:45:00'),
(5, 1, '2024-03-12 14:20:00'),
(9, 3, '2024-03-15 16:30:00'),
(12, 2, '2024-03-14 11:15:00'),
(20, 1, '2024-03-16 13:45:00'),
(25, 3, '2024-03-18 10:10:00');