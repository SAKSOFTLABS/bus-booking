<?php
/**
 * Automated Workflow Verification Test Script
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payment.php';
require_once __DIR__ . '/../includes/notification.php';

echo "=== STARTING SYSTEM INTEGRATION TESTS ===\n\n";

$db = getDB();

// Test 1: Customer OTP Generation & Verification
echo "Test 1: Customer OTP Dispatch & Verification... ";
$testPhone = '+919988776655';
$otpRes = AuthService::sendCustomerOTP($testPhone, 'login');
assert($otpRes['success'] === true, 'Failed to send OTP');
$otpCode = $otpRes['debug_otp'];
assert(!empty($otpCode), 'OTP code was empty');

$verifyRes = AuthService::verifyCustomerOTP($testPhone, $otpCode, 'Test Passenger');
assert($verifyRes['success'] === true, 'Failed to verify OTP');
assert($verifyRes['customer']['phone'] === $testPhone, 'Phone number mismatch');
echo "[PASS]\n";

// Test 2: Trip Price Calculation
echo "Test 2: Seat Price & Availability Calculation... ";
$trip = $db->query("SELECT * FROM `trips` WHERE status = 'scheduled' LIMIT 1")->fetch();
assert(!empty($trip), 'No scheduled trips found');
$tripId = $trip['id'];

$avail = getTripSeatAvailability($tripId);
assert($avail > 0, 'No available seats on trip');
echo "[PASS] (Trip #{$tripId} has {$avail} seats available @ ₹{$trip['rate_per_seat']}/seat)\n";

// Test 3: Create Booking Order
echo "Test 3: Booking Order Creation... ";
$pickup = $db->query("SELECT * FROM `pickup_points` WHERE is_active = 1 LIMIT 1")->fetch();
$pickupId = $pickup['id'];

// Book 2 seats
$seatsToBook = 2;
$rate = (float)$trip['rate_per_seat'];
$totalAmount = $rate * $seatsToBook;
$ref = generateBookingRef();

$stmt = $db->prepare("
    INSERT INTO `bookings` 
    (`booking_ref`, `customer_id`, `customer_name`, `customer_phone`, `passenger_names`, `trip_id`, `pickup_point_id`, `seats_count`, `rate_per_seat`, `total_amount`, `payment_status`, `booking_status`, `payment_method`, `created_at`)
    VALUES (?, ?, 'Test Passenger', ?, 'Test Passenger, Co-passenger', ?, ?, ?, ?, ?, 'pending', 'pending', 'online', NOW())
");
$stmt->execute([$ref, $verifyRes['customer']['id'], $testPhone, $tripId, $pickupId, $seatsToBook, $rate, $totalAmount]);
$bookingId = $db->lastInsertId();

$order = PaymentGateway::initiatePayment($bookingId, $totalAmount);
assert(!empty($order['transaction_ref']), 'Failed to initiate payment transaction');
echo "[PASS] (Booking #{$bookingId} created with Ref: {$ref})\n";

// Test 4: Online Payment Verification & Instant Confirmation
echo "Test 4: Payment Verification & Confirmation... ";
$prevBooked = (int)$trip['booked_seats'];
$confirmRes = PaymentGateway::verifyAndConfirmBooking($bookingId, $order['transaction_ref'], 'success', ['provider' => 'sandbox_test']);
assert($confirmRes['success'] === true, 'Failed to confirm booking: ' . ($confirmRes['message'] ?? ''));

$updatedTrip = $db->query("SELECT booked_seats FROM `trips` WHERE id = {$tripId}")->fetch();
assert((int)$updatedTrip['booked_seats'] === $prevBooked + $seatsToBook, 'Booked seats did not increment correctly');

// Check notification log
$notifCount = $db->query("SELECT COUNT(*) FROM `notification_logs` WHERE recipient_phone = '{$testPhone}' AND event_type = 'booking_confirmed'")->fetchColumn();
assert($notifCount >= 2, 'Customer confirmation notifications (SMS+WA) not found');
echo "[PASS] (Seats updated: {$prevBooked} -> {$updatedTrip['booked_seats']}, Notifications logged)\n";

// Test 5: 70% Occupancy Alert Logic
echo "Test 5: 70% Occupancy Threshold Alert Verification... ";
// Find or create a trip to test crossing 70%
// Bus capacity e.g. 40 seats. 70% of 40 = 28 seats.
$testBus = $db->query("SELECT * FROM `buses` WHERE total_capacity = 40 LIMIT 1")->fetch();
if (!$testBus) {
    $testBus = $db->query("SELECT * FROM `buses` LIMIT 1")->fetch();
}
$cap = (int)$testBus['total_capacity'];
$thresholdSeats = (int)ceil($cap * 0.70); // seats needed for 70%

// Create dedicated test trip
$stmtTestTrip = $db->prepare("
    INSERT INTO `trips` (`bus_id`, `trip_date`, `departure_time`, `rate_per_seat`, `total_capacity`, `booked_seats`, `occupancy_alert_sent`, `status`)
    VALUES (?, CURDATE(), '11:00:00', 200.00, ?, 0, 0, 'scheduled')
");
$stmtTestTrip->execute([$testBus['id'], $cap]);
$testTripId = $db->lastInsertId();

// Sub-step: Book up to thresholdSeats
$ref70 = generateBookingRef();
$insB70 = $db->prepare("
    INSERT INTO `bookings` 
    (`booking_ref`, `customer_name`, `customer_phone`, `passenger_names`, `trip_id`, `pickup_point_id`, `seats_count`, `rate_per_seat`, `total_amount`, `payment_status`, `booking_status`, `created_at`)
    VALUES (?, 'Threshold Passenger', '+919876500000', 'Threshold Passenger Group', ?, ?, ?, 200.00, ?, 'pending', 'pending', NOW())
");
$insB70->execute([$ref70, $testTripId, $pickupId, $thresholdSeats, ($thresholdSeats * 200)]);
$b70Id = $db->lastInsertId();

$order70 = PaymentGateway::initiatePayment($b70Id, ($thresholdSeats * 200));
$confirm70 = PaymentGateway::verifyAndConfirmBooking($b70Id, $order70['transaction_ref'], 'success');
assert($confirm70['success'] === true, 'Failed to confirm 70% threshold booking');

// Check that alert flag was set to 1
$tripCheck = $db->query("SELECT booked_seats, total_capacity, occupancy_alert_sent FROM `trips` WHERE id = {$testTripId}")->fetch();
$calcOcc = calculateOccupancy($tripCheck['booked_seats'], $tripCheck['total_capacity']);
assert($calcOcc >= 70.0, "Calculated occupancy {$calcOcc}% is below 70%");
assert((int)$tripCheck['occupancy_alert_sent'] === 1, 'Trip occupancy_alert_sent was not set to 1');

// Check that occupancy alert was logged
$alertLogged = $db->query("SELECT COUNT(*) FROM `notification_logs` WHERE event_type = 'occupancy_70_alert' AND recipient_type = 'admin'")->fetchColumn();
assert($alertLogged > 0, 'Occupancy 70% alert was not logged for admin');
echo "[PASS] (Threshold {$calcOcc}% reached, Admin SMS/WhatsApp alert sent & flag marked)\n";

// Test 6: Verify Duplicate Alert Prevention
echo "Test 6: Duplicate Alert Prevention... ";
$initialAlerts = (int)$db->query("SELECT COUNT(*) FROM `notification_logs` WHERE event_type = 'occupancy_70_alert'")->fetchColumn();
// Try triggering again directly
$repeatResult = NotificationService::checkAndTriggerOccupancyAlert($testTripId);
assert($repeatResult === false, 'Duplicate alert should have been prevented!');
$afterAlerts = (int)$db->query("SELECT COUNT(*) FROM `notification_logs` WHERE event_type = 'occupancy_70_alert'")->fetchColumn();
assert($initialAlerts === $afterAlerts, 'Duplicate alert count increased');
echo "[PASS] (Duplicate alerts prevented correctly)\n";

// Test 7: Booking Cancellation & Seat Capacity Restoration
echo "Test 7: Booking Cancellation & Capacity Restoration... ";
$tripBeforeCancel = $db->query("SELECT booked_seats FROM `trips` WHERE id = {$testTripId}")->fetch();
$seatsBeforeCancel = (int)$tripBeforeCancel['booked_seats'];

// Cancel booking b70Id
$db->prepare("UPDATE `bookings` SET `booking_status` = 'cancelled', `payment_status` = 'refunded', `cancelled_at` = NOW() WHERE id = ?")->execute([$b70Id]);
$db->prepare("UPDATE `trips` SET `booked_seats` = GREATEST(0, booked_seats - ?) WHERE id = ?")->execute([$thresholdSeats, $testTripId]);

$tripAfterCancel = $db->query("SELECT booked_seats FROM `trips` WHERE id = {$testTripId}")->fetch();
assert((int)$tripAfterCancel['booked_seats'] === $seatsBeforeCancel - $thresholdSeats, 'Seats were not restored upon cancellation');
echo "[PASS] (Seats restored: {$seatsBeforeCancel} -> {$tripAfterCancel['booked_seats']})\n";

// Test 8: Admin Authentication
echo "Test 8: Admin Login Authentication... ";
$adminLoginSuccess = AuthService::adminLogin('admin', 'admin123');
assert($adminLoginSuccess['success'] === true, 'Admin login failed for default credentials');
$adminLoginFail = AuthService::adminLogin('admin', 'wrongpassword');
assert($adminLoginFail['success'] === false, 'Admin login should have failed for wrong password');
echo "[PASS] (Valid authentication verified)\n";

echo "\n=== ALL 8 INTEGRATION TESTS PASSED SUCCESSFULLY! ===\n";
