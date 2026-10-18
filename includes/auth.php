<?php
/**
 * Authentication Service for Customer OTP and Admin
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notification.php';

class AuthService {

    // --- Customer OTP Authentication ---

    public static function sendCustomerOTP($phone, $type = 'login') {
        $db = getDB();
        $cleanPhone = preg_replace('/[^0-9+]/', '', trim($phone));
        if (strlen($cleanPhone) < 10) {
            return ['success' => false, 'message' => 'Please enter a valid mobile number'];
        }

        $otp = generateOTP(6);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Invalidate previous unused OTPs for this phone
        $stmtInvalidate = $db->prepare("UPDATE `otp_verifications` SET `is_used` = 1 WHERE `phone` = ? AND `is_used` = 0");
        $stmtInvalidate->execute([$cleanPhone]);

        // Insert new OTP
        $stmt = $db->prepare("
            INSERT INTO `otp_verifications` (`phone`, `otp_code`, `type`, `expires_at`, `is_used`, `created_at`)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$cleanPhone, $otp, $type, $expiresAt]);

        // Send via notification service
        NotificationService::notifyCustomerOTP($cleanPhone, $otp, $type);

        return [
            'success' => true,
            'message' => 'OTP sent successfully to ' . $cleanPhone,
            'phone' => $cleanPhone,
            // For convenience in testing/simulation mode, return otp in data
            'debug_otp' => $otp
        ];
    }

    public static function verifyCustomerOTP($phone, $otpCode, $name = '') {
        $db = getDB();
        $cleanPhone = preg_replace('/[^0-9+]/', '', trim($phone));
        $otpCode = trim($otpCode);

        $stmt = $db->prepare("
            SELECT * FROM `otp_verifications` 
            WHERE `phone` = ? AND `otp_code` = ? AND `is_used` = 0 AND `expires_at` > NOW()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$cleanPhone, $otpCode]);
        $record = $stmt->fetch();

        if (!$record) {
            return ['success' => false, 'message' => 'Invalid or expired OTP. Please try again.'];
        }

        // Mark OTP as used
        $updateStmt = $db->prepare("UPDATE `otp_verifications` SET `is_used` = 1 WHERE id = ?");
        $updateStmt->execute([$record['id']]);

        // Find or create customer
        $custStmt = $db->prepare("SELECT * FROM `customers` WHERE phone = ?");
        $custStmt->execute([$cleanPhone]);
        $customer = $custStmt->fetch();

        if (!$customer) {
            $customerName = !empty($name) ? trim($name) : 'Passenger ' . substr($cleanPhone, -4);
            $createStmt = $db->prepare("INSERT INTO `customers` (`name`, `phone`, `created_at`) VALUES (?, ?, NOW())");
            $createStmt->execute([$customerName, $cleanPhone]);
            $customerId = $db->lastInsertId();
            $customer = [
                'id' => $customerId,
                'name' => $customerName,
                'phone' => $cleanPhone
            ];
        } else if (!empty($name) && $customer['name'] !== $name) {
            $updateNameStmt = $db->prepare("UPDATE `customers` SET `name` = ? WHERE id = ?");
            $updateNameStmt->execute([trim($name), $customer['id']]);
            $customer['name'] = trim($name);
        }

        // Store customer in session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['customer'] = $customer;

        return [
            'success' => true,
            'message' => 'Login successful!',
            'customer' => $customer
        ];
    }

    public static function isCustomerLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['customer']);
    }

    public static function getCustomer() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['customer'] ?? null;
    }

    public static function customerLogout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['customer']);
    }

    // --- Admin Authentication ---

    public static function adminLogin($username, $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM `admins` WHERE username = ? LIMIT 1");
        $stmt->execute([trim($username)]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['admin'] = [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'name' => $admin['name'],
                'email' => $admin['email'],
                'role' => $admin['role']
            ];
            return ['success' => true, 'message' => 'Admin authentication successful!'];
        }

        return ['success' => false, 'message' => 'Invalid username or password'];
    }

    public static function isAdminLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['admin']);
    }

    public static function getAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['admin'] ?? null;
    }

    public static function adminLogout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['admin']);
    }

    public static function requireAdminAuth() {
        if (!self::isAdminLoggedIn()) {
            setFlash('error', 'Please login to access the administrator panel.');
            header('Location: ' . BASE_URL . '/admin/login.php');
            exit;
        }
    }
}
