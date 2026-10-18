<?php
/**
 * Customer Booking Success & Confirmation Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$bookingRef = cleanInput($_GET['ref'] ?? '');
if (empty($bookingRef)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("
    SELECT b.*, t.trip_date, t.departure_time, bs.name as bus_name, bs.registration_number, 
           p.name as pickup_name, p.landmark as pickup_landmark, p.address as pickup_address,
           pay.transaction_ref as pay_txn, pay.status as pay_status
    FROM `bookings` b
    JOIN `trips` t ON b.trip_id = t.id
    JOIN `buses` bs ON t.bus_id = bs.id
    JOIN `pickup_points` p ON b.pickup_point_id = p.id
    LEFT JOIN `payments` pay ON pay.booking_id = b.id
    WHERE b.booking_ref = ?
    ORDER BY pay.id DESC LIMIT 1
");
$stmt->execute([$bookingRef]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking not found.");
}

$settings = getSystemSettings();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="text-center mb-4">
        <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success mb-3">
            <i class="bi bi-check-circle-fill display-4"></i>
        </div>
        <h2 class="fw-extrabold text-navy">Booking Confirmed Successfully!</h2>
        <p class="text-muted">Thank you, <strong><?= htmlspecialchars($booking['customer_name']) ?></strong>. Your seat(s) have been reserved.</p>
        <div class="badge bg-primary-subtle text-primary border border-primary px-3 py-2 fs-6">
            Booking Reference: <strong><?= htmlspecialchars($booking['booking_ref']) ?></strong>
        </div>
    </div>

    <!-- Notification Alert Box -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <div class="alert alert-success d-flex align-items-center gap-3 shadow-sm rounded-3">
                <i class="bi bi-chat-check-fill fs-3 text-success"></i>
                <div class="small">
                    <strong>SMS & WhatsApp Confirmation Dispatched:</strong>
                    <div>Trip schedule, pickup details, and ticket reference have been sent to <strong><?= htmlspecialchars($booking['customer_phone']) ?></strong>.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ticket Voucher Card -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ticket-container">
                <div class="ticket-header d-flex justify-content-between align-items-center">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-bus-front-fill fs-4 text-warning"></i>
                            <h5 class="fw-bold mb-0 text-white"><?= APP_NAME ?></h5>
                        </div>
                        <span class="small text-white-50">Direct Express Transit Pass</span>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success px-3 py-2 fw-bold text-uppercase">
                            <?= strtoupper($booking['payment_status']) ?>
                        </span>
                    </div>
                </div>

                <div class="p-4 bg-white">
                    <div class="row g-4 mb-3">
                        <div class="col-md-6">
                            <span class="text-muted small d-block">Bus Service:</span>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($booking['bus_name']) ?></h5>
                            <span class="badge bg-light text-dark border small mt-1"><?= htmlspecialchars($booking['registration_number']) ?></span>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="text-muted small d-block">Trip Date & Departure:</span>
                            <h5 class="fw-bold mb-0 text-primary"><?= formatDate($booking['trip_date']) ?></h5>
                            <span class="fw-bold text-dark"><i class="bi bi-clock me-1"></i><?= formatTime($booking['departure_time']) ?></span>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-4 border">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <span class="text-muted small d-block"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Boarding Pickup:</span>
                                <strong class="fs-6"><?= htmlspecialchars($booking['pickup_name']) ?></strong>
                                <?php if (!empty($booking['pickup_landmark'])): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($booking['pickup_landmark']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block"><i class="bi bi-pin-map-fill text-success me-1"></i> Destination:</span>
                                <strong class="fs-6"><?= htmlspecialchars($settings['destination_name']) ?></strong>
                                <div class="small text-muted"><?= htmlspecialchars($settings['destination_landmark']) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 pb-3 border-bottom">
                        <div class="col-md-5">
                            <span class="text-muted small d-block">Devotee(s) Names:</span>
                            <strong class="text-navy"><?= htmlspecialchars(!empty($booking['passenger_names']) ? $booking['passenger_names'] : $booking['customer_name']) ?></strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Contact Mobile:</span>
                            <strong><?= htmlspecialchars($booking['customer_phone']) ?></strong>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <span class="text-muted small d-block">Seats Booked:</span>
                            <span class="badge bg-danger fs-6 px-3"><?= $booking['seats_count'] ?> Devotee(s)</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3">
                        <div>
                            <span class="text-muted small d-block">Transaction Ref:</span>
                            <span class="font-monospace small text-dark"><?= htmlspecialchars($booking['transaction_ref'] ?? 'N/A') ?></span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small d-block">Total Amount Paid:</span>
                            <h4 class="fw-extrabold text-success mb-0"><?= formatCurrency($booking['total_amount']) ?></h4>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light p-3 text-center border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="small text-muted">
                        <i class="bi bi-qr-code me-1"></i> Present this digital voucher or SMS to the boarding conductor.
                    </span>
                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>/ticket.php?ref=<?= urlencode($booking['booking_ref']) ?>" target="_blank" class="btn btn-dark btn-sm rounded-pill px-3">
                            <i class="bi bi-printer me-1"></i> Print / Download
                        </a>
                        <a href="<?= BASE_URL ?>/my-bookings.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="bi bi-clock-history me-1"></i> My Bookings
                        </a>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-1"></i> Book Another Seat
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
