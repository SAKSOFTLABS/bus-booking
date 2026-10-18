<?php
/**
 * API: Verify Customer OTP
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$phone = $_POST['phone'] ?? '';
$otpCode = $_POST['otp_code'] ?? '';
$name = $_POST['name'] ?? '';

if (empty($phone) || empty($otpCode)) {
    jsonResponse(false, 'Mobile number and OTP code are required');
}

$result = AuthService::verifyCustomerOTP($phone, $otpCode, $name);
jsonResponse($result['success'], $result['message'], $result);
