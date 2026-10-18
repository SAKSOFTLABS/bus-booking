<?php
/**
 * Application Configuration & Settings Loader
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/db.php';

// Dynamically determine Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
// Normalize to root of project
$basePath = preg_replace('#/(admin|api|includes).*$#', '', $scriptDir);
$basePath = rtrim($basePath, '/');
if (empty($basePath)) {
    // If running at the root of a domain or subdomain (e.g. bus.mykshethra.com), basePath is empty.
    // Only default to '/bus' if running on local development host.
    $basePath = ($host === 'localhost' || $host === '127.0.0.1') ? '/bus' : '';
}
define('BASE_URL', $protocol . $host . $basePath);
define('ROOT_PATH', dirname(__DIR__));

// Load dynamic system settings from database
function getSystemSettings() {
    static $settings = null;
    if ($settings === null) {
        try {
            $db = getDB();
            $stmt = $db->query("SELECT * FROM `settings` WHERE id = 1 LIMIT 1");
            $settings = $stmt->fetch();
            if (!$settings) {
                // Fallback default
                $settings = [
                    'destination_name' => 'Technopark Phase III Campus, Trivandrum',
                    'destination_name' => 'Sri Padmanabhaswamy Mahakshetram, Trivandrum',
                    'destination_landmark' => 'East Nada (Kizhakkekotta) Main Gopuram Arrival Bay',
                    'destination_address' => 'West Nada, Fort, East Fort, Pazhavangadi, Thiruvananthapuram, Kerala 695023',
                    'destination_contact' => '+91 94470 10808 / +91 98470 12345',
                    'occupancy_threshold' => 70.00,
                    'admin_alert_phone' => '+91 98765 43210',
                    'admin_alert_email' => 'admin@busbooking.local',
                    'payment_gateway_mode' => 'sandbox',
                    'razorpay_key_id' => 'rzp_test_sampleKey123',
                    'razorpay_key_secret' => 'rzp_test_sampleSecret456',
                    'notification_mode' => 'simulation',
                    'currency_symbol' => '₹',
                    'currency_code' => 'INR'
                ];
            }
        } catch (Exception $e) {
            $settings = [
                'destination_name' => 'Sri Padmanabhaswamy Mahakshetram, Trivandrum',
                'destination_landmark' => 'East Nada (Kizhakkekotta) Main Gopuram Arrival Bay',
                'destination_address' => 'West Nada, Fort, East Fort, Pazhavangadi, Thiruvananthapuram, Kerala 695023',
                'destination_contact' => '+91 94470 10808 / +91 98470 12345',
                'occupancy_threshold' => 70.00,
                'admin_alert_phone' => '+91 98765 43210',
                'admin_alert_email' => 'admin@busbooking.local',
                'payment_gateway_mode' => 'sandbox',
                'notification_mode' => 'simulation',
                'currency_symbol' => '₹',
                'currency_code' => 'INR'
            ];
        }
    }
    return $settings;
}

$GLOBALS['SETTINGS'] = getSystemSettings();
define('CURRENCY_SYMBOL', $GLOBALS['SETTINGS']['currency_symbol'] ?? '₹');
define('APP_NAME', 'Divya Yatra Express');
