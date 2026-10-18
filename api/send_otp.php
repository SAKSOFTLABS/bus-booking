<?php
/**
 * API: Send Customer OTP
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$phone = $_POST['phone'] ?? '';
$type = $_POST['type'] ?? 'booking';

if (empty($phone)) {
    jsonResponse(false, 'Mobile number is required');
}

$result = AuthService::sendCustomerOTP($phone, $type);
jsonResponse($result['success'], $result['message'], $result);
