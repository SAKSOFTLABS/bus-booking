-- Bus Booking System - Database Schema & Initial Data
-- For deployment to ServerByte (bus.mykshethra.com)

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `destination_name` varchar(255) NOT NULL,
  `destination_landmark` varchar(255) DEFAULT NULL,
  `destination_address` text DEFAULT NULL,
  `destination_contact` varchar(50) DEFAULT NULL,
  `occupancy_threshold` decimal(5,2) NOT NULL DEFAULT 70.00,
  `admin_alert_phone` varchar(20) NOT NULL DEFAULT '+919876543210',
  `admin_alert_email` varchar(100) NOT NULL DEFAULT 'admin@busbooking.local',
  `payment_gateway_mode` enum('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `razorpay_key_id` varchar(100) DEFAULT 'rzp_test_sampleKey123',
  `razorpay_key_secret` varchar(100) DEFAULT 'rzp_test_sampleSecret456',
  `notification_mode` enum('simulation','api') NOT NULL DEFAULT 'simulation',
  `sms_provider` varchar(50) NOT NULL DEFAULT 'simulation',
  `sms_api_key` varchar(255) DEFAULT NULL,
  `sms_sender_id` varchar(20) DEFAULT 'BUSTRV',
  `whatsapp_provider` varchar(50) NOT NULL DEFAULT 'simulation',
  `whatsapp_api_token` text DEFAULT NULL,
  `whatsapp_phone_number_id` varchar(100) DEFAULT NULL,
  `currency_symbol` varchar(10) NOT NULL DEFAULT '₹',
  `currency_code` varchar(10) NOT NULL DEFAULT 'INR',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `hero_title` varchar(255) DEFAULT 'Divya Darshan Yatra to Sri Padmanabhaswamy Temple',
  `hero_subtitle` text DEFAULT NULL,
  `hero_badge_text` varchar(150) DEFAULT 'ॐ Om Namo Narayanaya • Sacred Temple Pilgrimage',
  `hero_image_url` varchar(255) DEFAULT NULL,
  `hero_btn_text` varchar(100) DEFAULT 'Explore Yatra Buses',
  `hero_btn_link` varchar(255) DEFAULT '#available-buses',
  `temple_darshan_info` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `destination_name`, `destination_landmark`, `destination_address`, `destination_contact`, `occupancy_threshold`, `admin_alert_phone`, `admin_alert_email`, `payment_gateway_mode`, `razorpay_key_id`, `razorpay_key_secret`, `notification_mode`, `sms_provider`, `sms_api_key`, `sms_sender_id`, `whatsapp_provider`, `whatsapp_api_token`, `whatsapp_phone_number_id`, `currency_symbol`, `currency_code`, `updated_at`, `hero_title`, `hero_subtitle`, `hero_badge_text`, `hero_image_url`, `hero_btn_text`, `hero_btn_link`, `temple_darshan_info`) VALUES
('1', 'Sree Nagaraja Kshethram, Perumbavoor', 'East Nada (Kizhakkekotta) Main Gopuram Arrival Bay', 'West Nada, Fort, East Fort, Perumbavoor, Ernakulam, Kerala 695023', '+91 94470 10808 / +91 98470 12345', '70.00', '+91 98765 43210', 'admin@busbooking.local', 'sandbox', 'rzp_test_sampleKey123', 'rzp_test_sampleSecret456', 'simulation', 'simulation', NULL, 'BUSTRV', 'simulation', NULL, NULL, '₹', 'INR', '2026-10-12 21:59:52', 'Divya Darshanam Yatra to Sree Nagaraja Kshethram', 'Comfortable daily sacred temple transit with peaceful devotional atmosphere, divine chants, and timely arrival for auspicious morning and evening Darshan.', 'ॐ Sree Nagaraja Kshethram • Daily Yatra Special', 'https://images.unsplash.com/photo-1590050752117-238cb0fb12b1?auto=format&amp;fit=crop&amp;w=1200&amp;q=80', 'Book Yatra Bus Now', '#available-buses', 'Morning Darshan: 03:30 AM to 12:00 PM | Evening Deeparadhana: 05:00 PM to 08:30 PM. Traditional temple attire recommended.');

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`id`, `username`, `password_hash`, `name`, `email`, `phone`, `role`, `created_at`) VALUES
('1', 'admin', '$2y$10$bbXWfCMMg9DAlw/V0DykneZVhLtml9EzZBuWh/qHzeqrR8TTa0NEu', 'System Administrator', 'admin@busbooking.local', '+91 98765 43210', 'superadmin', '2026-10-12 18:01:57');

DROP TABLE IF EXISTS `buses`;
CREATE TABLE `buses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `total_capacity` int(11) NOT NULL,
  `default_rate_per_seat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_url` varchar(255) DEFAULT NULL,
  `bus_type` varchar(100) DEFAULT '2+2 Pushback AC Deluxe',
  `amenities` varchar(255) DEFAULT 'AC, Devotional Songs, Reclining Seats, Water Bottle',
  `rating` decimal(2,1) DEFAULT 4.8,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registration_number` (`registration_number`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `buses` (`id`, `name`, `registration_number`, `total_capacity`, `default_rate_per_seat`, `is_active`, `created_at`, `updated_at`, `image_url`, `bus_type`, `amenities`, `rating`) VALUES
('1', 'Sree Travels', 'KL-01-CB-4050', '45', '800.00', '1', '2026-10-12 18:01:57', '2026-10-18 13:40:35', 'assets/images/bus1_mockup.png', 'AC', '45 Seats, AC, Well Maintained', '4.9'),
('2', 'Divine Yatra', 'KL-01-AZ-8899', '50', '600.00', '1', '2026-10-12 18:01:57', '2026-10-18 13:40:35', 'assets/images/bus2_mockup.png', 'Non-AC', '50 Seats, Non-AC, Experienced Driver', '4.8'),
('3', 'Ananthapuri Express', 'KL-07-EX-1212', '40', '750.00', '1', '2026-10-12 18:01:57', '2026-10-18 13:40:35', 'assets/images/bus1_mockup.png', 'AC Deluxe', '40 Seats, AC, Pushback Seats', '4.8');

DROP TABLE IF EXISTS `pickup_points`;
CREATE TABLE `pickup_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pickup_points` (`id`, `name`, `landmark`, `address`, `display_order`, `is_active`, `created_at`) VALUES
('1', 'Thampanoor Central Bus Stand', 'Opposite Trivandrum Central Railway Station', 'Thampanoor, Trivandrum', '1', '1', '2026-10-12 18:01:57'),
('2', 'Pattom Junction', 'Near LIC Divisional Office & St. Marys School', 'Pattom, Trivandrum', '2', '1', '2026-10-12 18:01:57'),
('3', 'Kesavadasapuram', 'Near Mosque Junction & Bus Shelter', 'Kesavadasapuram, Trivandrum', '3', '1', '2026-10-12 18:01:57'),
('4', 'Ulloor Junction', 'Near Medical College Road Intersection', 'Ulloor, Trivandrum', '4', '1', '2026-10-12 18:01:57'),
('5', 'Sreekaryam Junction', 'Near College of Engineering (CET) Road', 'Sreekaryam, Trivandrum', '5', '1', '2026-10-12 18:01:57'),
('6', 'Kazhakkoottam Junction', 'Near Bypass Elevated Highway Flyover', 'Kazhakkoottam, Trivandrum', '6', '1', '2026-10-12 18:01:57'),
('7', 'Thrissur', 'Central Boarding Point', 'Thrissur, Kerala', '10', '1', '2026-10-18 13:40:35'),
('8', 'Guruvayur', 'Central Boarding Point', 'Guruvayur, Kerala', '11', '1', '2026-10-18 13:40:35'),
('9', 'Wadakkanchery', 'Central Boarding Point', 'Wadakkanchery, Kerala', '12', '1', '2026-10-18 13:40:35'),
('10', 'Kunnamkulam', 'Central Boarding Point', 'Kunnamkulam, Kerala', '13', '1', '2026-10-18 13:40:35'),
('11', 'Chavakkad', 'Central Boarding Point', 'Chavakkad, Kerala', '14', '1', '2026-10-18 13:40:35');

DROP TABLE IF EXISTS `trips`;
CREATE TABLE `trips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bus_id` int(11) NOT NULL,
  `trip_date` date NOT NULL,
  `departure_time` time NOT NULL,
  `rate_per_seat` decimal(10,2) NOT NULL,
  `total_capacity` int(11) NOT NULL,
  `booked_seats` int(11) NOT NULL DEFAULT 0,
  `occupancy_alert_sent` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `trip_date` (`trip_date`),
  KEY `bus_id` (`bus_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `trips` (`id`, `bus_id`, `trip_date`, `departure_time`, `rate_per_seat`, `total_capacity`, `booked_seats`, `occupancy_alert_sent`, `status`, `notes`, `created_at`, `updated_at`) VALUES
('1', '1', '2026-10-12', '07:30:00', '800.00', '45', '21', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:47:05'),
('2', '2', '2026-10-12', '08:45:00', '600.00', '50', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('3', '3', '2026-10-12', '17:30:00', '750.00', '40', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('4', '1', '2026-10-13', '07:30:00', '800.00', '45', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('5', '2', '2026-10-13', '08:45:00', '600.00', '50', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('6', '3', '2026-10-13', '17:30:00', '750.00', '40', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('7', '1', '2026-10-14', '07:30:00', '800.00', '45', '4', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('8', '2', '2026-10-14', '08:45:00', '600.00', '50', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('9', '3', '2026-10-14', '17:30:00', '750.00', '40', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('10', '1', '2026-10-15', '07:30:00', '800.00', '45', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('11', '2', '2026-10-15', '08:45:00', '600.00', '50', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('12', '3', '2026-10-15', '17:30:00', '750.00', '40', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('13', '1', '2026-10-16', '07:30:00', '800.00', '45', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('14', '2', '2026-10-16', '08:45:00', '600.00', '50', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('15', '3', '2026-10-16', '17:30:00', '750.00', '40', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('16', '1', '2026-10-17', '07:30:00', '800.00', '45', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('17', '2', '2026-10-17', '08:45:00', '600.00', '50', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('18', '3', '2026-10-17', '17:30:00', '750.00', '40', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('19', '1', '2026-10-18', '07:30:00', '800.00', '45', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('20', '2', '2026-10-18', '08:45:00', '600.00', '50', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('21', '3', '2026-10-18', '17:30:00', '750.00', '40', '0', '0', 'scheduled', NULL, '2026-10-12 18:01:57', '2026-10-18 13:40:35'),
('22', '1', '2026-10-12', '11:00:00', '800.00', '45', '0', '1', 'scheduled', NULL, '2026-10-12 18:15:55', '2026-10-18 13:40:35'),
('23', '1', '2026-10-12', '11:00:00', '800.00', '45', '0', '1', 'scheduled', NULL, '2026-10-12 18:16:28', '2026-10-18 13:40:35'),
('24', '1', '2026-10-12', '11:00:00', '800.00', '45', '0', '1', 'scheduled', NULL, '2026-10-12 18:46:34', '2026-10-18 13:40:35'),
('25', '1', '2026-10-12', '11:00:00', '800.00', '45', '0', '1', 'scheduled', NULL, '2026-10-12 21:31:12', '2026-10-18 13:40:35'),
('26', '1', '2026-10-18', '11:00:00', '800.00', '45', '0', '1', 'scheduled', NULL, '2026-10-18 13:32:40', '2026-10-18 13:40:35'),
('27', '3', '2026-10-18', '11:00:00', '200.00', '40', '0', '1', 'scheduled', NULL, '2026-10-18 13:40:41', '2026-10-18 13:40:41'),
('28', '3', '2026-10-18', '11:00:00', '200.00', '40', '0', '1', 'scheduled', NULL, '2026-10-18 13:45:59', '2026-10-18 13:45:59'),
('29', '3', '2026-10-18', '11:00:00', '200.00', '40', '0', '1', 'scheduled', NULL, '2026-10-18 13:47:05', '2026-10-18 13:47:05');

DROP TABLE IF EXISTS `trip_pickup_points`;
CREATE TABLE `trip_pickup_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trip_id` int(11) NOT NULL,
  `pickup_point_id` int(11) NOT NULL,
  `pickup_time` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  KEY `pickup_point_id` (`pickup_point_id`)
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `trip_pickup_points` (`id`, `trip_id`, `pickup_point_id`, `pickup_time`) VALUES
('1', '1', '1', '06:45:00'),
('2', '1', '2', '07:00:00'),
('3', '1', '3', '07:10:00'),
('4', '1', '4', '07:20:00'),
('5', '1', '5', '07:30:00'),
('6', '1', '6', '07:45:00'),
('7', '2', '1', '08:00:00'),
('8', '2', '2', '08:15:00'),
('9', '2', '3', '08:25:00'),
('10', '2', '4', '08:35:00'),
('11', '2', '5', '08:45:00'),
('12', '2', '6', '09:00:00'),
('13', '3', '1', '16:45:00'),
('14', '3', '2', '17:00:00'),
('15', '3', '3', '17:10:00'),
('16', '3', '4', '17:20:00'),
('17', '3', '5', '17:30:00'),
('18', '3', '6', '17:45:00'),
('19', '4', '1', '06:45:00'),
('20', '4', '2', '07:00:00'),
('21', '4', '3', '07:10:00'),
('22', '4', '4', '07:20:00'),
('23', '4', '5', '07:30:00'),
('24', '4', '6', '07:45:00'),
('25', '5', '1', '08:00:00'),
('26', '5', '2', '08:15:00'),
('27', '5', '3', '08:25:00'),
('28', '5', '4', '08:35:00'),
('29', '5', '5', '08:45:00'),
('30', '5', '6', '09:00:00'),
('31', '6', '1', '16:45:00'),
('32', '6', '2', '17:00:00'),
('33', '6', '3', '17:10:00'),
('34', '6', '4', '17:20:00'),
('35', '6', '5', '17:30:00'),
('36', '6', '6', '17:45:00'),
('37', '7', '1', '06:45:00'),
('38', '7', '2', '07:00:00'),
('39', '7', '3', '07:10:00'),
('40', '7', '4', '07:20:00'),
('41', '7', '5', '07:30:00'),
('42', '7', '6', '07:45:00'),
('43', '8', '1', '08:00:00'),
('44', '8', '2', '08:15:00'),
('45', '8', '3', '08:25:00'),
('46', '8', '4', '08:35:00'),
('47', '8', '5', '08:45:00'),
('48', '8', '6', '09:00:00'),
('49', '9', '1', '16:45:00'),
('50', '9', '2', '17:00:00'),
('51', '9', '3', '17:10:00'),
('52', '9', '4', '17:20:00'),
('53', '9', '5', '17:30:00'),
('54', '9', '6', '17:45:00'),
('55', '10', '1', '06:45:00'),
('56', '10', '2', '07:00:00'),
('57', '10', '3', '07:10:00'),
('58', '10', '4', '07:20:00'),
('59', '10', '5', '07:30:00'),
('60', '10', '6', '07:45:00'),
('61', '11', '1', '08:00:00'),
('62', '11', '2', '08:15:00'),
('63', '11', '3', '08:25:00'),
('64', '11', '4', '08:35:00'),
('65', '11', '5', '08:45:00'),
('66', '11', '6', '09:00:00'),
('67', '12', '1', '16:45:00'),
('68', '12', '2', '17:00:00'),
('69', '12', '3', '17:10:00'),
('70', '12', '4', '17:20:00'),
('71', '12', '5', '17:30:00'),
('72', '12', '6', '17:45:00'),
('73', '13', '1', '06:45:00'),
('74', '13', '2', '07:00:00'),
('75', '13', '3', '07:10:00'),
('76', '13', '4', '07:20:00'),
('77', '13', '5', '07:30:00'),
('78', '13', '6', '07:45:00'),
('79', '14', '1', '08:00:00'),
('80', '14', '2', '08:15:00'),
('81', '14', '3', '08:25:00'),
('82', '14', '4', '08:35:00'),
('83', '14', '5', '08:45:00'),
('84', '14', '6', '09:00:00'),
('85', '15', '1', '16:45:00'),
('86', '15', '2', '17:00:00'),
('87', '15', '3', '17:10:00'),
('88', '15', '4', '17:20:00'),
('89', '15', '5', '17:30:00'),
('90', '15', '6', '17:45:00'),
('91', '16', '1', '06:45:00'),
('92', '16', '2', '07:00:00'),
('93', '16', '3', '07:10:00'),
('94', '16', '4', '07:20:00'),
('95', '16', '5', '07:30:00'),
('96', '16', '6', '07:45:00'),
('97', '17', '1', '08:00:00'),
('98', '17', '2', '08:15:00'),
('99', '17', '3', '08:25:00'),
('100', '17', '4', '08:35:00'),
('101', '17', '5', '08:45:00'),
('102', '17', '6', '09:00:00'),
('103', '18', '1', '16:45:00'),
('104', '18', '2', '17:00:00'),
('105', '18', '3', '17:10:00'),
('106', '18', '4', '17:20:00'),
('107', '18', '5', '17:30:00'),
('108', '18', '6', '17:45:00'),
('109', '19', '1', '06:45:00'),
('110', '19', '2', '07:00:00'),
('111', '19', '3', '07:10:00'),
('112', '19', '4', '07:20:00'),
('113', '19', '5', '07:30:00'),
('114', '19', '6', '07:45:00'),
('115', '20', '1', '08:00:00'),
('116', '20', '2', '08:15:00'),
('117', '20', '3', '08:25:00'),
('118', '20', '4', '08:35:00'),
('119', '20', '5', '08:45:00'),
('120', '20', '6', '09:00:00'),
('121', '21', '1', '16:45:00'),
('122', '21', '2', '17:00:00'),
('123', '21', '3', '17:10:00'),
('124', '21', '4', '17:20:00'),
('125', '21', '5', '17:30:00'),
('126', '21', '6', '17:45:00');

DROP TABLE IF EXISTS `hero_slides`;
CREATE TABLE `hero_slides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `subtitle` text DEFAULT NULL,
  `image_url` text NOT NULL,
  `link_url` varchar(255) NOT NULL DEFAULT '#available-buses',
  `button_text` varchar(100) DEFAULT 'Book Yatra Bus',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hero_slides` (`id`, `title`, `subtitle`, `image_url`, `link_url`, `button_text`, `display_order`, `is_active`, `created_at`) VALUES
('1', 'Visit the Divine Abode', 'Comfortable and Safe Bus Travel to Your Favourite Temple', 'assets/images/hero_temple_bg.png', '#available-buses', 'Book Your Darshan', '1', '1', '2026-10-12 21:28:03'),
('2', 'Garuda Yatra Multi-Axle Volvo AC Express', 'Experience holy chants, reclining pushback seats, mineral water & hassle-free temple boarding along all major transit points.', 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=1600&q=80', 'bus-details.php?id=1', 'View Garuda Volvo Schedule', '2', '1', '2026-10-12 21:28:03'),
('3', 'Auspicious Evening Deeparadhana Special Service', 'Special evening departures designed for sacred sunset pooja & divine lamp darshan with traditional temple attire escort.', 'https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=1600&q=80', '#available-buses', 'Explore Evening Yatra', '3', '1', '2026-10-12 21:28:03');

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `otp_verifications`;
CREATE TABLE `otp_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `type` enum('login','booking') NOT NULL DEFAULT 'booking',
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `phone` (`phone`,`otp_code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_ref` varchar(30) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `pickup_point_id` int(11) NOT NULL,
  `seats_count` int(11) NOT NULL,
  `rate_per_seat` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `booking_status` enum('confirmed','cancelled','pending') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) NOT NULL DEFAULT 'online',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `passenger_names` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_ref` (`booking_ref`),
  KEY `trip_id` (`trip_id`),
  KEY `customer_phone` (`customer_phone`),
  KEY `booking_ref_2` (`booking_ref`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `transaction_ref` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `gateway` varchar(50) NOT NULL DEFAULT 'razorpay_simulator',
  `status` enum('initiated','success','failed','refunded') NOT NULL DEFAULT 'initiated',
  `gateway_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `transaction_ref` (`transaction_ref`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `notification_logs`;
CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_type` enum('customer','admin') NOT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `channel` enum('sms','whatsapp') NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed','simulated') NOT NULL DEFAULT 'sent',
  `response_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recipient_phone` (`recipient_phone`),
  KEY `event_type` (`event_type`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
