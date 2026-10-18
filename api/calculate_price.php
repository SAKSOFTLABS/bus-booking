<?php
/**
 * API: Calculate Price and Check Availability
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$tripId = (int)($_POST['trip_id'] ?? $_GET['trip_id'] ?? 0);
$seats = max(1, (int)($_POST['seats'] ?? $_GET['seats'] ?? 1));

if (!$tripId) {
    jsonResponse(false, 'Trip ID is required');
}

$db = getDB();
$stmt = $db->prepare("
    SELECT t.*, b.name as bus_name, b.registration_number 
    FROM `trips` t 
    JOIN `buses` b ON t.bus_id = b.id 
    WHERE t.id = ?
");
$stmt->execute([$tripId]);
$trip = $stmt->fetch();

if (!$trip) {
    jsonResponse(false, 'Trip not found');
}

$availableSeats = max(0, $trip['total_capacity'] - $trip['booked_seats']);

if ($seats > $availableSeats) {
    jsonResponse(false, "Only {$availableSeats} seats remaining for this trip.", [
        'available_seats' => $availableSeats,
        'rate_per_seat' => (float)$trip['rate_per_seat'],
        'total_amount' => 0
    ]);
}

$rate = (float)$trip['rate_per_seat'];
$totalAmount = $rate * $seats;

jsonResponse(true, 'Calculation successful', [
    'trip_id' => $tripId,
    'rate_per_seat' => $rate,
    'seats' => $seats,
    'total_amount' => $totalAmount,
    'total_amount_formatted' => formatCurrency($totalAmount),
    'available_seats' => $availableSeats
]);
