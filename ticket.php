<?php
/**
 * Printable Ticket Voucher
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$bookingRef = cleanInput($_GET['ref'] ?? '');
if (empty($bookingRef)) {
    die("Invalid Ticket Reference.");
}

$db = getDB();
$stmt = $db->prepare("
    SELECT b.*, t.trip_date, t.departure_time, bs.name as bus_name, bs.registration_number, 
           p.name as pickup_name, p.landmark as pickup_landmark, p.address as pickup_address
    FROM `bookings` b
    JOIN `trips` t ON b.trip_id = t.id
    JOIN `buses` bs ON t.bus_id = bs.id
    JOIN `pickup_points` p ON b.pickup_point_id = p.id
    WHERE b.booking_ref = ?
");
$stmt->execute([$bookingRef]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Ticket not found.");
}

$settings = getSystemSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket: <?= htmlspecialchars($booking['booking_ref']) ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body { background: #f1f5f9; padding: 20px; font-family: 'Plus Jakarta Sans', sans-serif; }
        .barcode {
            font-family: 'Courier New', Courier, monospace;
            letter-spacing: 5px;
            font-size: 20px;
            font-weight: bold;
            background: #f8fafc;
            padding: 8px 15px;
            display: inline-block;
            border: 1px dashed #94a3b8;
        }
    </style>
</head>
<body>

<div class="ticket-container my-4">
    <!-- Ticket Brand Header -->
    <div class="ticket-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div class="brand-icon">
                <i class="bi bi-bus-front-fill"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-white"><?= APP_NAME ?></h4>
                <small class="text-white-50">Official Boarding Pass & Travel Voucher</small>
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-success fs-6 px-3 py-2 text-uppercase"><?= strtoupper($booking['payment_status']) ?></span>
            <div class="small text-white-50 mt-1"><?= $booking['booking_ref'] ?></div>
        </div>
    </div>

    <!-- Body -->
    <div class="p-4 bg-white">
        <!-- Route Banner -->
        <div class="p-3 bg-light rounded-3 mb-4 border">
            <div class="row align-items-center">
                <div class="col-5">
                    <span class="text-muted small d-block">FROM (PICKUP POINT)</span>
                    <strong class="fs-5 text-dark"><?= htmlspecialchars($booking['pickup_name']) ?></strong>
                    <div class="small text-muted"><?= htmlspecialchars($booking['pickup_landmark'] ?? '') ?></div>
                </div>
                <div class="col-2 text-center">
                    <i class="bi bi-arrow-right-circle-fill fs-3 text-primary"></i>
                </div>
                <div class="col-5 text-end">
                    <span class="text-muted small d-block">TO (FIXED DESTINATION)</span>
                    <strong class="fs-5 text-dark"><?= htmlspecialchars($settings['destination_name']) ?></strong>
                    <div class="small text-muted"><?= htmlspecialchars($settings['destination_landmark'] ?? '') ?></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-3">
            <div class="col-6">
                <span class="text-muted small d-block">Trip Date:</span>
                <strong class="fs-6"><?= formatDate($booking['trip_date']) ?></strong>
            </div>
            <div class="col-6">
                <span class="text-muted small d-block">Scheduled Departure:</span>
                <strong class="fs-6 text-primary"><?= formatTime($booking['departure_time']) ?></strong>
            </div>
            <div class="col-6">
                <span class="text-muted small d-block">Bus Service:</span>
                <span class="fw-bold"><?= htmlspecialchars($booking['bus_name']) ?></span> (<?= htmlspecialchars($booking['registration_number']) ?>)
            </div>
            <div class="col-6">
                <span class="text-muted small d-block">Primary Contact:</span>
                <span class="fw-bold"><?= htmlspecialchars($booking['customer_name']) ?></span> (<?= htmlspecialchars($booking['customer_phone']) ?>)
            </div>
            <div class="col-12 mt-2">
                <span class="text-muted small d-block">Devotee(s) Travelling:</span>
                <div class="p-2 bg-light rounded border fw-bold text-dark">
                    <i class="bi bi-people-fill text-danger me-1"></i> <?= htmlspecialchars(!empty($booking['passenger_names']) ? $booking['passenger_names'] : $booking['customer_name']) ?>
                </div>
            </div>
        </div>

        <hr>

        <div class="row g-3 align-items-center">
            <div class="col-6">
                <span class="text-muted small d-block">Total Seats Booked:</span>
                <h4 class="fw-bold text-dark mb-0"><?= $booking['seats_count'] ?> Seat(s)</h4>
                <small class="text-muted"><?= formatCurrency($booking['rate_per_seat']) ?> / seat</small>
            </div>
            <div class="col-6 text-end">
                <span class="text-muted small d-block">Total Fare Paid:</span>
                <h3 class="fw-extrabold text-success mb-0"><?= formatCurrency($booking['total_amount']) ?></h3>
                <small class="text-muted">Txn: <?= htmlspecialchars($booking['transaction_ref'] ?? 'PAID') ?></small>
            </div>
        </div>

        <div class="text-center my-4">
            <div class="barcode">||| | ||||| || |||| ||| | |||</div>
            <div class="small text-muted mt-1"><?= htmlspecialchars($booking['booking_ref']) ?></div>
        </div>

        <div class="alert alert-light border small text-muted mb-0">
            <strong>Important Travel Instructions:</strong>
            <ul class="mb-0 ps-3 mt-1">
                <li>Please arrive at your pickup point at least 10 minutes prior to scheduled departure.</li>
                <li>Show this printed e-ticket or SMS confirmation to the bus conductor upon boarding.</li>
                <li>Support Helpline: <?= htmlspecialchars($settings['destination_contact']) ?></li>
            </ul>
        </div>
    </div>

    <!-- Print Action Footer -->
    <div class="p-3 bg-light text-center border-top no-print">
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 me-2 fw-bold">
            <i class="bi bi-printer me-1"></i> Print Ticket
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary rounded-pill px-4">
            Close
        </button>
    </div>
</div>

</body>
</html>
