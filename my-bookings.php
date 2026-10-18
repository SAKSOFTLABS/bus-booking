<?php
/**
 * Customer Trip History & OTP Authentication Portal
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/notification.php';

$db = getDB();

// Handle Logout
if (isset($_GET['logout'])) {
    AuthService::customerLogout();
    header('Location: ' . BASE_URL . '/my-bookings.php');
    exit;
}

// Handle Cancel Booking action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $reason = cleanInput($_POST['reason'] ?? 'Cancelled by passenger');
    $customer = AuthService::getCustomer();

    if ($customer && $bookingId) {
        $stmt = $db->prepare("SELECT * FROM `bookings` WHERE id = ? AND (customer_id = ? OR customer_phone = ?)");
        $stmt->execute([$bookingId, $customer['id'], $customer['phone']]);
        $b = $stmt->fetch();

        if ($b && $b['booking_status'] === 'confirmed') {
            // Update booking status
            $db->prepare("
                UPDATE `bookings` 
                SET `booking_status` = 'cancelled', `cancellation_reason` = ?, `cancelled_at` = NOW() 
                WHERE id = ?
            ")->execute([$reason, $bookingId]);

            // Release booked seats back to trip
            $db->prepare("
                UPDATE `trips` 
                SET `booked_seats` = GREATEST(0, booked_seats - ?) 
                WHERE id = ?
            ")->execute([$b['seats_count'], $b['trip_id']]);

            // Fetch details for notification
            $details = $db->prepare("
                SELECT b.*, t.trip_date, t.departure_time, bs.name as bus_name, p.name as pickup_name
                FROM `bookings` b
                JOIN `trips` t ON b.trip_id = t.id
                JOIN `buses` bs ON t.bus_id = bs.id
                JOIN `pickup_points` p ON b.pickup_point_id = p.id
                WHERE b.id = ?
            ");
            $details->execute([$bookingId]);
            $fullB = $details->fetch();

            NotificationService::notifyBookingCancelled($fullB, $reason);

            setFlash('success', 'Booking has been cancelled successfully. Notifications have been dispatched.');
            header('Location: ' . BASE_URL . '/my-bookings.php');
            exit;
        }
    }
}

$customer = AuthService::getCustomer();
$bookings = [];

if ($customer) {
    $stmt = $db->prepare("
        SELECT b.*, t.trip_date, t.departure_time, bs.name as bus_name, bs.registration_number, 
               p.name as pickup_name, p.landmark as pickup_landmark
        FROM `bookings` b
        JOIN `trips` t ON b.trip_id = t.id
        JOIN `buses` bs ON t.bus_id = bs.id
        JOIN `pickup_points` p ON b.pickup_point_id = p.id
        WHERE b.customer_phone = ? OR b.customer_id = ?
        ORDER BY t.trip_date DESC, t.departure_time DESC
    ");
    $stmt->execute([$customer['phone'], $customer['id']]);
    $bookings = $stmt->fetchAll();
}

$settings = getSystemSettings();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <?php if (hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars(getFlash('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!$customer): ?>
        <!-- Customer Not Logged In: Show OTP Login Form -->
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-navy text-white text-center py-4">
                        <div class="brand-icon mx-auto mb-2">
                            <i class="bi bi-person-fill-lock"></i>
                        </div>
                        <h4 class="fw-bold mb-1">Customer OTP Login</h4>
                        <p class="text-white-50 small mb-0">Access your past trips, download tickets & manage bookings</p>
                    </div>

                    <div class="card-body p-4">
                        <!-- Step 1: Request OTP -->
                        <div id="otpStep1">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">+91</span>
                                    <input type="tel" class="form-control" id="otpModalPhone" placeholder="Enter 10-digit number" maxlength="15" autofocus>
                                </div>
                                <div class="form-text small">We'll send a 6-digit verification code to this mobile number.</div>
                            </div>
                            <button type="button" class="btn btn-primary w-100 rounded-pill py-2 fw-bold" id="btnSendOtpModal">
                                Send OTP <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>

                        <!-- Step 2: Verify OTP -->
                        <div id="otpStep2" class="d-none">
                            <div class="text-center mb-3">
                                <i class="bi bi-chat-left-dots text-primary display-6"></i>
                                <h6 class="fw-bold mt-2">Enter Verification Code</h6>
                                <p class="text-muted small">Enter the 6-digit OTP sent to <strong id="otpSentToPhone"></strong></p>
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control text-center fs-3 font-monospace letter-spacing-2" id="otpModalCode" placeholder="••••••" maxlength="6">
                            </div>
                            <button type="button" class="btn btn-success w-100 rounded-pill py-2 fw-bold" id="btnVerifyOtpModal">
                                Verify & Access Bookings
                            </button>
                        </div>
                    </div>

                    <div class="card-footer bg-light p-3 text-center small text-muted">
                        Don't have an account? Simply enter your phone number to automatically sign in or view your guest bookings!
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Customer Is Logged In: Show Trip History & Account Details -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary mb-1">Customer Portal</span>
                <h3 class="fw-bold text-navy mb-0">Welcome, <?= htmlspecialchars($customer['name']) ?>!</h3>
                <p class="text-muted small mb-0"><i class="bi bi-phone me-1"></i> <?= htmlspecialchars($customer['phone']) ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary rounded-pill px-3 btn-sm fw-bold">
                    <i class="bi bi-plus-circle me-1"></i> Book New Seat
                </a>
                <a href="<?= BASE_URL ?>/my-bookings.php?logout=1" class="btn btn-outline-danger rounded-pill px-3 btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                </a>
            </div>
        </div>

        <!-- Bookings List Table / Cards -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-ticket-detailed me-2 text-primary"></i> Your Bookings & Trip History</h5>
                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill"><?= count($bookings) ?> Total Record(s)</span>
            </div>

            <div class="card-body p-4">
                <?php if (empty($bookings)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-ticket-perforated display-4 text-muted d-block mb-3"></i>
                        <h5>No bookings found for <?= htmlspecialchars($customer['phone']) ?></h5>
                        <p class="text-muted small">You haven't booked any bus trips yet.</p>
                        <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary rounded-pill px-4">Book Your First Seat</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Trip Date & Time</th>
                                    <th>Bus & Route</th>
                                    <th>Boarding Point</th>
                                    <th>Seats</th>
                                    <th>Total Paid</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b): 
                                    $isUpcoming = strtotime($b['trip_date']) >= strtotime(date('Y-m-d'));
                                ?>
                                    <tr>
                                        <td>
                                            <strong class="font-monospace text-primary"><?= htmlspecialchars($b['booking_ref']) ?></strong>
                                            <div class="small text-muted"><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <strong><?= formatDate($b['trip_date']) ?></strong>
                                            <div class="small text-muted"><i class="bi bi-clock me-1"></i><?= formatTime($b['departure_time']) ?></div>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($b['bus_name']) ?></strong>
                                            <div class="small text-muted">To: <?= htmlspecialchars($settings['destination_name']) ?></div>
                                            <?php if (!empty($b['passenger_names'])): ?>
                                                <div class="small text-danger"><i class="bi bi-people-fill me-1"></i><?= htmlspecialchars($b['passenger_names']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                            <?= htmlspecialchars($b['pickup_name']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark rounded-pill px-2"><?= $b['seats_count'] ?> Seat(s)</span>
                                        </td>
                                        <td>
                                            <strong class="text-success"><?= formatCurrency($b['total_amount']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($b['booking_status'] === 'confirmed'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success">Confirmed</span>
                                            <?php elseif ($b['booking_status'] === 'cancelled'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger">Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="<?= BASE_URL ?>/ticket.php?ref=<?= urlencode($b['booking_ref']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill" title="View & Print Ticket">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                                <?php if ($b['booking_status'] === 'confirmed' && $isUpcoming): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $b['id'] ?>" title="Cancel Booking">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Cancel Modal -->
                                            <?php if ($b['booking_status'] === 'confirmed' && $isUpcoming): ?>
                                                <div class="modal fade" id="cancelModal<?= $b['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content rounded-4 border-0">
                                                            <form method="POST">
                                                                <input type="hidden" name="action" value="cancel_booking">
                                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                                <div class="modal-header bg-danger text-white border-0">
                                                                    <h6 class="modal-title fw-bold">Cancel Booking <?= $b['booking_ref'] ?></h6>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body text-start p-4">
                                                                    <p>Are you sure you want to cancel your booking for <strong><?= $b['seats_count'] ?> seat(s)</strong> on <strong><?= formatDate($b['trip_date']) ?></strong>?</p>
                                                                    <div class="mb-3">
                                                                        <label class="form-label small fw-bold">Reason for Cancellation</label>
                                                                        <textarea name="reason" class="form-control" rows="2" placeholder="e.g. Change of plans" required></textarea>
                                                                    </div>
                                                                    <div class="alert alert-warning py-2 small mb-0">
                                                                        Seats will be released back to the trip and confirmation SMS/WhatsApp will be sent.
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-0 p-3">
                                                                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Keep Booking</button>
                                                                    <button type="submit" class="btn btn-danger rounded-pill fw-bold">Confirm Cancellation</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
