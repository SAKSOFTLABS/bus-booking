<?php
/**
 * Administrator Bookings Management & Sales Operations
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../../includes/notification.php';

$db = getDB();

// Handle Actions (Cancel / Refund / Confirm)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $bookingId = (int)($_POST['booking_id'] ?? 0);

    if ($action === 'cancel_booking' && $bookingId) {
        $reason = cleanInput($_POST['reason'] ?? 'Cancelled by Admin');
        
        $stmt = $db->prepare("SELECT * FROM `bookings` WHERE id = ?");
        $stmt->execute([$bookingId]);
        $b = $stmt->fetch();

        if ($b && $b['booking_status'] === 'confirmed') {
            $db->prepare("
                UPDATE `bookings` 
                SET `booking_status` = 'cancelled', `payment_status` = 'refunded', `cancellation_reason` = ?, `cancelled_at` = NOW() 
                WHERE id = ?
            ")->execute([$reason, $bookingId]);

            // Release seats back to trip
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

            setFlash('success', "Booking {$b['booking_ref']} cancelled and marked as refunded. Notifications sent.");
        }
        header('Location: ' . BASE_URL . '/admin/bookings.php');
        exit;
    }
}

// Filters
$filterStatus = cleanInput($_GET['status'] ?? '');
$filterBus = (int)($_GET['bus_id'] ?? 0);
$filterDate = cleanInput($_GET['date'] ?? '');
$search = cleanInput($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($filterStatus)) {
    $where[] = "b.booking_status = ?";
    $params[] = $filterStatus;
}
if ($filterBus > 0) {
    $where[] = "t.bus_id = ?";
    $params[] = $filterBus;
}
if (!empty($filterDate)) {
    $where[] = "t.trip_date = ?";
    $params[] = $filterDate;
}
if (!empty($search)) {
    $where[] = "(b.booking_ref LIKE ? OR b.customer_name LIKE ? OR b.customer_phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT b.*, t.trip_date, t.departure_time, bs.name as bus_name, bs.registration_number, 
           p.name as pickup_name, p.landmark as pickup_landmark
    FROM `bookings` b
    JOIN `trips` t ON b.trip_id = t.id
    JOIN `buses` bs ON t.bus_id = bs.id
    JOIN `pickup_points` p ON b.pickup_point_id = p.id
    WHERE {$whereSql}
    ORDER BY b.id DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Buses list for filter dropdown
$allBuses = $db->query("SELECT id, name, registration_number FROM `buses` ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h3 class="fw-extrabold text-navy mb-1">Bookings & Ticket Sales</h3>
        <p class="text-muted small mb-0">Search, filter, view booking vouchers, and process cancellations or refunds.</p>
    </div>
    <button class="btn btn-outline-secondary rounded-pill px-3 btn-sm" onclick="exportTableToCSV('allBookingsTable', 'bookings_sales.csv')">
        <i class="bi bi-download me-1"></i> Export CSV
    </button>
</div>

<!-- Filter Bar Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Ref / Passenger / Phone" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- All Statuses --</option>
                    <option value="confirmed" <?= ($filterStatus === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                    <option value="cancelled" <?= ($filterStatus === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    <option value="pending" <?= ($filterStatus === 'pending') ? 'selected' : '' ?>>Pending</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="bus_id" class="form-select form-select-sm">
                    <option value="">-- All Buses --</option>
                    <?php foreach ($allBuses as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($filterBus == $b['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['registration_number']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($filterDate) ?>" placeholder="Filter Date">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold">Filter</button>
                <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-light btn-sm rounded-pill border">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Bookings Table Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="allBookingsTable">
                <thead class="table-light">
                    <tr>
                        <th>Booking Ref</th>
                        <th>Passenger</th>
                        <th>Mobile</th>
                        <th>Trip Date & Time</th>
                        <th>Bus</th>
                        <th>Boarding Stop</th>
                        <th>Seats</th>
                        <th>Total Paid</th>
                        <th>Payment Status</th>
                        <th>Booking Status</th>
                        <th class="text-end no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr><td colspan="11" class="text-center py-4 text-muted">No bookings match the criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>
                                    <strong class="font-monospace text-primary"><?= htmlspecialchars($b['booking_ref']) ?></strong>
                                    <div class="text-muted" style="font-size: 0.72rem;"><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($b['customer_name']) ?></strong>
                                    <?php if (!empty($b['passenger_names'])): ?>
                                        <div class="text-danger small" style="font-size: 0.72rem;"><i class="bi bi-people me-1"></i><?= htmlspecialchars($b['passenger_names']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($b['customer_phone']) ?></td>
                                <td>
                                    <strong><?= formatDate($b['trip_date']) ?></strong>
                                    <div class="text-muted small"><?= formatTime($b['departure_time']) ?></div>
                                </td>
                                <td>
                                    <span><?= htmlspecialchars($b['bus_name']) ?></span>
                                    <div class="text-muted small font-monospace"><?= htmlspecialchars($b['registration_number']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($b['pickup_name']) ?></td>
                                <td><span class="badge bg-dark rounded-pill"><?= $b['seats_count'] ?></span></td>
                                <td><strong class="text-success"><?= formatCurrency($b['total_amount']) ?></strong></td>
                                <td>
                                    <?php if ($b['payment_status'] === 'paid'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success">Paid</span>
                                    <?php elseif ($b['payment_status'] === 'refunded'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info">Refunded</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger">Failed/Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($b['booking_status'] === 'confirmed'): ?>
                                        <span class="badge bg-success">Confirmed</span>
                                    <?php elseif ($b['booking_status'] === 'cancelled'): ?>
                                        <span class="badge bg-danger">Cancelled</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end no-export">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= BASE_URL ?>/ticket.php?ref=<?= urlencode($b['booking_ref']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill" title="Print Ticket">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <?php if ($b['booking_status'] === 'confirmed'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-bs-toggle="modal" data-bs-target="#adminCancelModal<?= $b['id'] ?>" title="Cancel & Refund">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Admin Cancellation Modal -->
                                    <?php if ($b['booking_status'] === 'confirmed'): ?>
                                        <div class="modal fade" id="adminCancelModal<?= $b['id'] ?>" tabindex="-1">
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
                                                            <p>Cancel booking for <strong><?= htmlspecialchars($b['customer_name']) ?></strong> (<?= $b['seats_count'] ?> seats)?</p>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold">Reason for Cancellation</label>
                                                                <textarea name="reason" class="form-control" rows="2" placeholder="e.g. Schedule cancellation or refund request" required></textarea>
                                                            </div>
                                                            <div class="alert alert-warning py-2 small mb-0">
                                                                Will release <?= $b['seats_count'] ?> seat(s) back to the bus capacity and notify passenger via SMS & WhatsApp.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 p-3">
                                                            <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-danger rounded-pill fw-bold">Confirm & Refund</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
