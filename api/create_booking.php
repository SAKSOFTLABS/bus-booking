<?php
/**
 * API: Create Pending Booking & Initiate Online Payment
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payment.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$tripId = (int)($_POST['trip_id'] ?? 0);
$pickupPointId = (int)($_POST['pickup_point_id'] ?? 0);
$seatsCount = (int)($_POST['seats_count'] ?? 0);
$customerName = cleanInput($_POST['customer_name'] ?? '');
$customerPhone = cleanInput($_POST['customer_phone'] ?? '');

if (!$tripId || !$pickupPointId || $seatsCount < 1) {
    jsonResponse(false, 'Please select a valid trip, pickup point, and seats count');
}

if (empty($customerName) || empty($customerPhone)) {
    jsonResponse(false, 'Please provide your name and mobile number');
}

$cleanPhone = preg_replace('/[^0-9+]/', '', $customerPhone);
if (strlen($cleanPhone) < 10) {
    jsonResponse(false, 'Please enter a valid 10-digit mobile number');
}

$db = getDB();

try {
    // Check Trip availability
    $stmtTrip = $db->prepare("
        SELECT t.*, b.name as bus_name, b.registration_number 
        FROM `trips` t 
        JOIN `buses` b ON t.bus_id = b.id 
        WHERE t.id = ? AND t.status = 'scheduled'
    ");
    $stmtTrip->execute([$tripId]);
    $trip = $stmtTrip->fetch();

    if (!$trip) {
        jsonResponse(false, 'Trip is not available or has been cancelled');
    }

    $availableSeats = $trip['total_capacity'] - $trip['booked_seats'];
    if ($seatsCount > $availableSeats) {
        jsonResponse(false, "Only {$availableSeats} seats are available for this trip. Please reduce seat quantity.");
    }

    // Check Pickup Point
    $stmtPickup = $db->prepare("SELECT * FROM `pickup_points` WHERE id = ? AND is_active = 1");
    $stmtPickup->execute([$pickupPointId]);
    $pickup = $stmtPickup->fetch();

    if (!$pickup) {
        jsonResponse(false, 'Selected pickup point is invalid or inactive');
    }

    // Find or create customer
    $customerId = null;
    $custStmt = $db->prepare("SELECT id FROM `customers` WHERE phone = ?");
    $custStmt->execute([$cleanPhone]);
    $existingCust = $custStmt->fetch();

    if ($existingCust) {
        $customerId = $existingCust['id'];
        // Update customer name if provided
        $db->prepare("UPDATE `customers` SET name = ? WHERE id = ?")->execute([$customerName, $customerId]);
    } else {
        $insCust = $db->prepare("INSERT INTO `customers` (`name`, `phone`, `created_at`) VALUES (?, ?, NOW())");
        $insCust->execute([$customerName, $cleanPhone]);
        $customerId = $db->lastInsertId();
    }

    $passengerNamesInput = $_POST['passenger_names'] ?? [];
    if (is_array($passengerNamesInput)) {
        $passengerNamesStr = implode(', ', array_filter(array_map('cleanInput', $passengerNamesInput)));
    } else {
        $passengerNamesStr = cleanInput($passengerNamesInput);
    }
    if (empty($passengerNamesStr)) {
        $passengerNamesStr = $customerName;
    }

    $ratePerSeat = (float)$trip['rate_per_seat'];
    $totalAmount = $ratePerSeat * $seatsCount;
    $bookingRef = generateBookingRef();

    // Insert pending booking
    $insBook = $db->prepare("
        INSERT INTO `bookings` 
        (`booking_ref`, `customer_id`, `customer_name`, `customer_phone`, `passenger_names`, `trip_id`, `pickup_point_id`, `seats_count`, `rate_per_seat`, `total_amount`, `payment_status`, `booking_status`, `payment_method`, `created_at`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', 'online', NOW())
    ");
    $insBook->execute([
        $bookingRef,
        $customerId,
        $customerName,
        $cleanPhone,
        $passengerNamesStr,
        $tripId,
        $pickupPointId,
        $seatsCount,
        $ratePerSeat,
        $totalAmount
    ]);
    $bookingId = $db->lastInsertId();

    // Initiate Payment
    $paymentOrder = PaymentGateway::initiatePayment($bookingId, $totalAmount, 'INR');

    // Auto-login customer in session if not logged in
    if (!AuthService::isCustomerLoggedIn()) {
        $_SESSION['customer'] = [
            'id' => $customerId,
            'name' => $customerName,
            'phone' => $cleanPhone
        ];
    }

    jsonResponse(true, 'Booking order created successfully', [
        'booking_id' => $bookingId,
        'booking_ref' => $bookingRef,
        'amount' => $totalAmount,
        'amount_formatted' => formatCurrency($totalAmount),
        'customer_name' => $customerName,
        'customer_phone' => $cleanPhone,
        'trip_date' => formatDate($trip['trip_date']),
        'departure_time' => formatTime($trip['departure_time']),
        'bus_name' => $trip['bus_name'],
        'pickup_name' => $pickup['name'],
        'seats' => $seatsCount,
        'payment_order' => $paymentOrder
    ]);

} catch (Exception $e) {
    jsonResponse(false, 'Booking creation error: ' . $e->getMessage());
}
