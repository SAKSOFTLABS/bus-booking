<?php
/**
 * API: Verify Payment & Confirm Booking
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payment.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
$transactionRef = cleanInput($_POST['transaction_ref'] ?? '');
$status = cleanInput($_POST['status'] ?? 'success'); // success or failed
$paymentMethod = cleanInput($_POST['payment_method'] ?? 'card');
$rawResponse = $_POST['raw_response'] ?? ['method' => $paymentMethod, 'timestamp' => time()];

if (!$bookingId || empty($transactionRef)) {
    jsonResponse(false, 'Booking ID and transaction reference are required');
}

$confirmResult = PaymentGateway::verifyAndConfirmBooking($bookingId, $transactionRef, $status, $rawResponse);

if ($confirmResult['success']) {
    $booking = $confirmResult['booking'];
    jsonResponse(true, 'Payment verified and booking confirmed successfully!', [
        'booking_ref' => $booking['booking_ref'],
        'booking_id' => $booking['id'],
        'redirect_url' => BASE_URL . '/booking-success.php?ref=' . urlencode($booking['booking_ref'])
    ]);
} else {
    jsonResponse(false, $confirmResult['message'] ?? 'Payment verification failed');
}
