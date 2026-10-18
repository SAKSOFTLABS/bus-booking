<?php
/**
 * Bus Booking System - Database Installer & Migrator
 */

require_once __DIR__ . '/config/db.php';

function installDatabase($forceReset = false) {
    $db = getDB();

    $queries = [
        // Settings table
        "CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `destination_name` VARCHAR(255) NOT NULL,
            `destination_landmark` VARCHAR(255) NULL,
            `destination_address` TEXT NULL,
            `destination_contact` VARCHAR(50) NULL,
            `occupancy_threshold` DECIMAL(5,2) NOT NULL DEFAULT 70.00,
            `admin_alert_phone` VARCHAR(20) NOT NULL DEFAULT '+919876543210',
            `admin_alert_email` VARCHAR(100) NOT NULL DEFAULT 'admin@busbooking.local',
            `payment_gateway_mode` ENUM('sandbox', 'live') NOT NULL DEFAULT 'sandbox',
            `razorpay_key_id` VARCHAR(100) NULL DEFAULT 'rzp_test_sampleKey123',
            `razorpay_key_secret` VARCHAR(100) NULL DEFAULT 'rzp_test_sampleSecret456',
            `notification_mode` ENUM('simulation', 'api') NOT NULL DEFAULT 'simulation',
            `sms_provider` VARCHAR(50) NOT NULL DEFAULT 'simulation',
            `sms_api_key` VARCHAR(255) NULL,
            `sms_sender_id` VARCHAR(20) NULL DEFAULT 'BUSTRV',
            `whatsapp_provider` VARCHAR(50) NOT NULL DEFAULT 'simulation',
            `whatsapp_api_token` TEXT NULL,
            `whatsapp_phone_number_id` VARCHAR(100) NULL,
            `currency_symbol` VARCHAR(10) NOT NULL DEFAULT '₹',
            `currency_code` VARCHAR(10) NOT NULL DEFAULT 'INR',
            `hero_title` VARCHAR(255) NULL DEFAULT 'Divya Darshan Yatra to Sri Padmanabhaswamy Temple',
            `hero_subtitle` TEXT NULL,
            `hero_badge_text` VARCHAR(150) NULL DEFAULT 'ॐ Om Namo Bhagavate Vasudevaya • Daily Yatra Special',
            `hero_image_url` VARCHAR(255) NULL,
            `hero_btn_text` VARCHAR(100) NULL DEFAULT 'Book Yatra Bus Now',
            `hero_btn_link` VARCHAR(255) NULL DEFAULT '#available-buses',
            `temple_darshan_info` TEXT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Admin users table
        "CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password_hash` VARCHAR(255) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NULL,
            `phone` VARCHAR(20) NULL,
            `role` VARCHAR(20) NOT NULL DEFAULT 'admin',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Buses table
        "CREATE TABLE IF NOT EXISTS `buses` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `registration_number` VARCHAR(50) NOT NULL UNIQUE,
            `total_capacity` INT NOT NULL,
            `default_rate_per_seat` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `bus_type` VARCHAR(100) NULL DEFAULT '2+2 Pushback AC Deluxe',
            `image_url` VARCHAR(255) NULL,
            `amenities` VARCHAR(255) NULL DEFAULT 'AC, Devotional Songs, Pushback Seats, Water Bottle',
            `rating` DECIMAL(2,1) NULL DEFAULT 4.8,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Pickup points table
        "CREATE TABLE IF NOT EXISTS `pickup_points` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL,
            `landmark` VARCHAR(255) NULL,
            `address` TEXT NULL,
            `display_order` INT NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Trips table
        "CREATE TABLE IF NOT EXISTS `trips` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `bus_id` INT NOT NULL,
            `trip_date` DATE NOT NULL,
            `departure_time` TIME NOT NULL,
            `rate_per_seat` DECIMAL(10,2) NOT NULL,
            `total_capacity` INT NOT NULL,
            `booked_seats` INT NOT NULL DEFAULT 0,
            `occupancy_alert_sent` TINYINT(1) NOT NULL DEFAULT 0,
            `status` ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
            `notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`trip_date`),
            INDEX (`bus_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Trip Pickup Points mapping table
        "CREATE TABLE IF NOT EXISTS `trip_pickup_points` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `trip_id` INT NOT NULL,
            `pickup_point_id` INT NOT NULL,
            `pickup_time` TIME NULL,
            INDEX (`trip_id`),
            INDEX (`pickup_point_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Customers table
        "CREATE TABLE IF NOT EXISTS `customers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(20) NOT NULL UNIQUE,
            `email` VARCHAR(100) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // OTP Verifications table
        "CREATE TABLE IF NOT EXISTS `otp_verifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `phone` VARCHAR(20) NOT NULL,
            `otp_code` VARCHAR(10) NOT NULL,
            `type` ENUM('login', 'booking') NOT NULL DEFAULT 'booking',
            `expires_at` DATETIME NOT NULL,
            `is_used` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`phone`, `otp_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Bookings table
        "CREATE TABLE IF NOT EXISTS `bookings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `booking_ref` VARCHAR(30) NOT NULL UNIQUE,
            `customer_id` INT NULL,
            `customer_name` VARCHAR(100) NOT NULL,
            `customer_phone` VARCHAR(20) NOT NULL,
            `passenger_names` TEXT NULL,
            `trip_id` INT NOT NULL,
            `pickup_point_id` INT NOT NULL,
            `seats_count` INT NOT NULL,
            `rate_per_seat` DECIMAL(10,2) NOT NULL,
            `total_amount` DECIMAL(10,2) NOT NULL,
            `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
            `booking_status` ENUM('confirmed', 'cancelled', 'pending') NOT NULL DEFAULT 'pending',
            `payment_method` VARCHAR(50) NOT NULL DEFAULT 'online',
            `transaction_ref` VARCHAR(100) NULL,
            `cancellation_reason` TEXT NULL,
            `cancelled_at` DATETIME NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`trip_id`),
            INDEX (`customer_phone`),
            INDEX (`booking_ref`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Payments table
        "CREATE TABLE IF NOT EXISTS `payments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `booking_id` INT NOT NULL,
            `transaction_ref` VARCHAR(100) NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL,
            `gateway` VARCHAR(50) NOT NULL DEFAULT 'razorpay_simulator',
            `status` ENUM('initiated', 'success', 'failed', 'refunded') NOT NULL DEFAULT 'initiated',
            `gateway_response` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`booking_id`),
            INDEX (`transaction_ref`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Notification Logs table
        "CREATE TABLE IF NOT EXISTS `notification_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `recipient_type` ENUM('customer', 'admin') NOT NULL,
            `recipient_phone` VARCHAR(20) NOT NULL,
            `channel` ENUM('sms', 'whatsapp') NOT NULL,
            `event_type` VARCHAR(50) NOT NULL,
            `message` TEXT NOT NULL,
            `status` ENUM('sent', 'failed', 'simulated') NOT NULL DEFAULT 'sent',
            `response_data` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`recipient_phone`),
            INDEX (`event_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        // Hero Slider Banners table
        "CREATE TABLE IF NOT EXISTS `hero_slides` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NULL,
            `subtitle` TEXT NULL,
            `image_url` TEXT NOT NULL,
            `link_url` VARCHAR(255) NOT NULL DEFAULT '#available-buses',
            `button_text` VARCHAR(100) NULL DEFAULT 'Book Yatra Bus',
            `display_order` INT NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    ];

    foreach ($queries as $sql) {
        $db->exec($sql);
    }

    // Seed default settings if empty
    $checkSettings = $db->query("SELECT COUNT(*) FROM `settings`")->fetchColumn();
    if ($checkSettings == 0) {
        $stmt = $db->prepare("INSERT INTO `settings` 
            (`id`, `destination_name`, `destination_landmark`, `destination_address`, `destination_contact`, `occupancy_threshold`, `admin_alert_phone`, `admin_alert_email`, `payment_gateway_mode`, `razorpay_key_id`, `razorpay_key_secret`, `notification_mode`, `sms_provider`, `sms_sender_id`, `whatsapp_provider`, `currency_symbol`, `currency_code`) 
            VALUES (1, :name, :landmark, :address, :contact, :threshold, :admin_phone, :admin_email, 'sandbox', 'rzp_test_sampleKey123', 'rzp_test_sampleSecret456', 'simulation', 'simulation', 'BUSTRV', 'simulation', '₹', 'INR')");
        $stmt->execute([
            ':name' => 'Technopark Phase III Campus, Trivandrum',
            ':landmark' => 'Main Gate Reception / Transit Hub',
            ':address' => 'Technopark Campus, Kulathoor, Trivandrum, Kerala 695583',
            ':contact' => '+91 98470 12345',
            ':threshold' => 70.00,
            ':admin_phone' => '+91 98765 43210',
            ':admin_email' => 'admin@busbooking.local'
        ]);
    }

    // Seed default admin if empty
    $checkAdmin = $db->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();
    if ($checkAdmin == 0) {
        $passwordHash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO `admins` (`username`, `password_hash`, `name`, `email`, `phone`, `role`) VALUES (:user, :pass, :name, :email, :phone, 'superadmin')");
        $stmt->execute([
            ':user' => 'admin',
            ':pass' => $passwordHash,
            ':name' => 'System Administrator',
            ':email' => 'admin@busbooking.local',
            ':phone' => '+91 98765 43210'
        ]);
    }

    // Seed sample buses if empty
    $checkBuses = $db->query("SELECT COUNT(*) FROM `buses`")->fetchColumn();
    if ($checkBuses == 0) {
        $buses = [
            ['Royal Express AC', 'KL-01-CB-4050', 40, 250.00],
            ['City Deluxe SuperFast', 'KL-01-AZ-8899', 50, 180.00],
            ['Starline Executive Sleeper', 'KL-07-EX-1212', 35, 320.00]
        ];
        $stmt = $db->prepare("INSERT INTO `buses` (`name`, `registration_number`, `total_capacity`, `default_rate_per_seat`, `is_active`) VALUES (?, ?, ?, ?, 1)");
        foreach ($buses as $bus) {
            $stmt->execute($bus);
        }
    }

    // Seed pickup points if empty
    $checkPickups = $db->query("SELECT COUNT(*) FROM `pickup_points`")->fetchColumn();
    if ($checkPickups == 0) {
        $pickups = [
            ['Thampanoor Central Bus Stand', 'Opposite Trivandrum Central Railway Station', 'Thampanoor, Trivandrum', 1],
            ['Pattom Junction', 'Near LIC Divisional Office & St. Marys School', 'Pattom, Trivandrum', 2],
            ['Kesavadasapuram', 'Near Mosque Junction & Bus Shelter', 'Kesavadasapuram, Trivandrum', 3],
            ['Ulloor Junction', 'Near Medical College Road Intersection', 'Ulloor, Trivandrum', 4],
            ['Sreekaryam Junction', 'Near College of Engineering (CET) Road', 'Sreekaryam, Trivandrum', 5],
            ['Kazhakkoottam Junction', 'Near Bypass Elevated Highway Flyover', 'Kazhakkoottam, Trivandrum', 6]
        ];
        $stmt = $db->prepare("INSERT INTO `pickup_points` (`name`, `landmark`, `address`, `display_order`, `is_active`) VALUES (?, ?, ?, ?, 1)");
        foreach ($pickups as $p) {
            $stmt->execute($p);
        }
    }

    // Seed sample trips for today & next 7 days if empty
    $checkTrips = $db->query("SELECT COUNT(*) FROM `trips`")->fetchColumn();
    if ($checkTrips == 0) {
        $busList = $db->query("SELECT id, name, total_capacity, default_rate_per_seat FROM `buses` ORDER BY id ASC")->fetchAll();
        $pickupList = $db->query("SELECT id FROM `pickup_points` ORDER BY display_order ASC")->fetchAll();

        $today = date('Y-m-d');
        for ($i = 0; $i < 7; $i++) {
            $tripDate = date('Y-m-d', strtotime("+$i days"));
            
            // Trip 1 (Morning)
            if (isset($busList[0])) {
                $b = $busList[0];
                $stmt = $db->prepare("INSERT INTO `trips` (`bus_id`, `trip_date`, `departure_time`, `rate_per_seat`, `total_capacity`, `booked_seats`, `status`) VALUES (?, ?, '07:30:00', ?, ?, 0, 'scheduled')");
                $stmt->execute([$b['id'], $tripDate, $b['default_rate_per_seat'], $b['total_capacity']]);
                $tripId = $db->lastInsertId();

                // Map pickup points
                $pickupStmt = $db->prepare("INSERT INTO `trip_pickup_points` (`trip_id`, `pickup_point_id`, `pickup_time`) VALUES (?, ?, ?)");
                $times = ['06:45:00', '07:00:00', '07:10:00', '07:20:00', '07:30:00', '07:45:00'];
                foreach ($pickupList as $idx => $p) {
                    $t = $times[$idx] ?? '07:00:00';
                    $pickupStmt->execute([$tripId, $p['id'], $t]);
                }
            }

            // Trip 2 (Mid-morning)
            if (isset($busList[1])) {
                $b = $busList[1];
                $stmt = $db->prepare("INSERT INTO `trips` (`bus_id`, `trip_date`, `departure_time`, `rate_per_seat`, `total_capacity`, `booked_seats`, `status`) VALUES (?, ?, '08:45:00', ?, ?, 0, 'scheduled')");
                $stmt->execute([$b['id'], $tripDate, $b['default_rate_per_seat'], $b['total_capacity']]);
                $tripId = $db->lastInsertId();

                $pickupStmt = $db->prepare("INSERT INTO `trip_pickup_points` (`trip_id`, `pickup_point_id`, `pickup_time`) VALUES (?, ?, ?)");
                $times = ['08:00:00', '08:15:00', '08:25:00', '08:35:00', '08:45:00', '09:00:00'];
                foreach ($pickupList as $idx => $p) {
                    $t = $times[$idx] ?? '08:15:00';
                    $pickupStmt->execute([$tripId, $p['id'], $t]);
                }
            }

            // Trip 3 (Evening)
            if (isset($busList[2])) {
                $b = $busList[2];
                $stmt = $db->prepare("INSERT INTO `trips` (`bus_id`, `trip_date`, `departure_time`, `rate_per_seat`, `total_capacity`, `booked_seats`, `status`) VALUES (?, ?, '17:30:00', ?, ?, 0, 'scheduled')");
                $stmt->execute([$b['id'], $tripDate, $b['default_rate_per_seat'], $b['total_capacity']]);
                $tripId = $db->lastInsertId();

                $pickupStmt = $db->prepare("INSERT INTO `trip_pickup_points` (`trip_id`, `pickup_point_id`, `pickup_time`) VALUES (?, ?, ?)");
                $times = ['16:45:00', '17:00:00', '17:10:00', '17:20:00', '17:30:00', '17:45:00'];
                foreach ($pickupList as $idx => $p) {
                    $t = $times[$idx] ?? '17:00:00';
                    $pickupStmt->execute([$tripId, $p['id'], $t]);
                }
            }
        }
    }

    return true;
}

// If run from CLI or Web
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    try {
        installDatabase();
        if (php_sapi_name() === 'cli') {
            echo "Database successfully installed and seeded!\n";
            echo "Default Admin: admin / admin123\n";
        } else {
            echo "<div style='font-family:sans-serif;padding:30px;text-align:center;'>
                <h2 style='color:#16a34a;'>Database Installed & Seeded Successfully!</h2>
                <p>Default Admin: <strong>admin</strong> | Password: <strong>admin123</strong></p>
                <p><a href='index.php' style='display:inline-block;padding:10px 20px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;margin:5px;'>Visit Customer Website</a>
                   <a href='admin/login.php' style='display:inline-block;padding:10px 20px;background:#0f172a;color:#fff;text-decoration:none;border-radius:6px;margin:5px;'>Admin Portal</a></p>
            </div>";
        }
    } catch (Exception $e) {
        echo "Installation Error: " . $e->getMessage();
    }
}
