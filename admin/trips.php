<?php
/**
 * Trip Management & Real-time Occupancy Monitoring
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../../includes/notification.php';

$db = getDB();

// Handle Add / Edit Trip POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create' || $action === 'update') {
        $busId = (int)($_POST['bus_id'] ?? 0);
        $tripDate = cleanInput($_POST['trip_date'] ?? '');
        $departureTime = cleanInput($_POST['departure_time'] ?? '');
        $ratePerSeat = (float)($_POST['rate_per_seat'] ?? 0);
        $status = cleanInput($_POST['status'] ?? 'scheduled');
        $selectedPickups = $_POST['pickups'] ?? [];

        // Fetch bus capacity
        $busStmt = $db->prepare("SELECT total_capacity FROM `buses` WHERE id = ?");
        $busStmt->execute([$busId]);
        $bus = $busStmt->fetch();

        if (!$bus || empty($tripDate) || empty($departureTime) || $ratePerSeat <= 0) {
            setFlash('error', 'Please fill in all required fields (Bus, Trip Date, Departure Time, and Rate > 0).');
        } else {
            $totalCapacity = $bus['total_capacity'];

            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO `trips` 
                    (`bus_id`, `trip_date`, `departure_time`, `rate_per_seat`, `total_capacity`, `booked_seats`, `status`, `created_at`)
                    VALUES (?, ?, ?, ?, ?, 0, ?, NOW())
                ");
                $stmt->execute([$busId, $tripDate, $departureTime, $ratePerSeat, $totalCapacity, $status]);
                $tripId = $db->lastInsertId();

                // Map pickup points
                if (!empty($selectedPickups)) {
                    $mapStmt = $db->prepare("INSERT INTO `trip_pickup_points` (`trip_id`, `pickup_point_id`) VALUES (?, ?)");
                    foreach ($selectedPickups as $pId) {
                        $mapStmt->execute([$tripId, (int)$pId]);
                    }
                }
                setFlash('success', 'New trip scheduled successfully!');
            } else {
                $tripId = (int)($_POST['trip_id'] ?? 0);
                $stmt = $db->prepare("
                    UPDATE `trips` 
                    SET `bus_id` = ?, `trip_date` = ?, `departure_time` = ?, `rate_per_seat` = ?, `status` = ?
                    WHERE `id` = ?
                ");
                $stmt->execute([$busId, $tripDate, $departureTime, $ratePerSeat, $status, $tripId]);

                // Update pickup points
                $db->prepare("DELETE FROM `trip_pickup_points` WHERE `trip_id` = ?")->execute([$tripId]);
                if (!empty($selectedPickups)) {
                    $mapStmt = $db->prepare("INSERT INTO `trip_pickup_points` (`trip_id`, `pickup_point_id`) VALUES (?, ?)");
                    foreach ($selectedPickups as $pId) {
                        $mapStmt->execute([$tripId, (int)$pId]);
                    }
                }
                setFlash('success', 'Trip updated successfully!');
            }
            header('Location: ' . BASE_URL . '/admin/trips.php');
            exit;
        }
    } elseif ($action === 'test_alert') {
        // Force test trigger 70% alert
        $tripId = (int)($_POST['trip_id'] ?? 0);
        $triggered = NotificationService::checkAndTriggerOccupancyAlert($tripId);
        if ($triggered) {
            setFlash('success', 'Occupancy alert triggered! SMS & WhatsApp notifications have been sent to Admin.');
        } else {
            setFlash('error', 'Alert not triggered: trip either has not reached 70% occupancy or alert was already sent.');
        }
        header('Location: ' . BASE_URL . '/admin/trips.php');
        exit;
    }
}

// Fetch Trips
$trips = $db->query("
    SELECT t.*, b.name as bus_name, b.registration_number,
           (SELECT COUNT(*) FROM `bookings` bks WHERE bks.trip_id = t.id AND bks.booking_status = 'confirmed') as booking_count
    FROM `trips` t
    JOIN `buses` b ON t.bus_id = b.id
    ORDER BY t.trip_date DESC, t.departure_time ASC
")->fetchAll();

// Fetch active buses & pickup points for the modal
$activeBuses = $db->query("SELECT * FROM `buses` WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$allPickups = $db->query("SELECT * FROM `pickup_points` WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h3 class="fw-extrabold text-navy mb-1">Trip Schedules & Live Occupancy</h3>
        <p class="text-muted small mb-0">Manage departure schedules, passenger capacity, seat rates, and 70% threshold alerts.</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#tripModal">
        <i class="bi bi-calendar-plus me-1"></i> Schedule New Trip
    </button>
</div>

<!-- Trips Table Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0"><i class="bi bi-calendar3-range text-primary me-2"></i> Scheduled Trips (<?= count($trips) ?>)</h5>
        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="exportTableToCSV('tripsTable', 'trip_schedules.csv')">
            <i class="bi bi-download me-1"></i> Export CSV
        </button>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tripsTable">
                <thead class="table-light">
                    <tr>
                        <th>Trip ID</th>
                        <th>Date & Departure</th>
                        <th>Bus & Reg No.</th>
                        <th>Rate / Seat</th>
                        <th>Capacity</th>
                        <th>Booked / Remaining</th>
                        <th>Occupancy %</th>
                        <th>70% Alert</th>
                        <th>Status</th>
                        <th class="text-end no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($trips)): ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">No trips scheduled.</td></tr>
                    <?php else: ?>
                        <?php foreach ($trips as $t): 
                            $occ = calculateOccupancy($t['booked_seats'], $t['total_capacity']);
                            $remaining = max(0, $t['total_capacity'] - $t['booked_seats']);
                            $is70Crossed = ($occ >= 70);
                        ?>
                            <tr class="<?= $is70Crossed ? 'table-warning bg-opacity-25' : '' ?>">
                                <td><strong class="font-monospace">#<?= $t['id'] ?></strong></td>
                                <td>
                                    <strong><?= formatDate($t['trip_date']) ?></strong>
                                    <div class="small text-muted"><i class="bi bi-clock me-1"></i><?= formatTime($t['departure_time']) ?></div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($t['bus_name']) ?></strong>
                                    <div class="small text-muted font-monospace"><?= htmlspecialchars($t['registration_number']) ?></div>
                                </td>
                                <td><strong class="text-primary"><?= formatCurrency($t['rate_per_seat']) ?></strong></td>
                                <td><?= $t['total_capacity'] ?> Seats</td>
                                <td>
                                    <strong><?= $t['booked_seats'] ?></strong> Booked
                                    <div class="small text-muted"><?= $remaining ?> remaining</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="occupancy-progress flex-grow-1" style="width: 70px;">
                                            <div class="progress-bar <?= $is70Crossed ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= min(100, $occ) ?>%"></div>
                                        </div>
                                        <span class="small fw-bold <?= $is70Crossed ? 'text-danger' : 'text-dark' ?>"><?= $occ ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($t['occupancy_alert_sent']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success" title="Admin alert was dispatched via SMS/WhatsApp">
                                            <i class="bi bi-check2-all me-1"></i> Alert Sent
                                        </span>
                                    <?php elseif ($is70Crossed): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="test_alert">
                                            <input type="hidden" name="trip_id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger py-0 px-2 rounded-pill small">
                                                <i class="bi bi-bell"></i> Send Alert
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Normal (&lt;70%)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($t['status'] === 'scheduled'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success">Scheduled</span>
                                    <?php elseif ($t['status'] === 'completed'): ?>
                                        <span class="badge bg-secondary-subtle text-dark border">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end no-export">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill" 
                                            onclick='openEditTripModal(<?= json_encode($t) ?>)'>
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= SCHEDULE TRIP MODAL ================= -->
<div class="modal fade" id="tripModal" tabindex="-1" aria-labelledby="tripModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" id="tripForm">
                <input type="hidden" name="action" id="tripFormAction" value="create">
                <input type="hidden" name="trip_id" id="tripFormId" value="">

                <div class="modal-header bg-navy text-white border-0 py-3 px-4">
                    <h6 class="modal-title fw-bold" id="tripModalLabel">Schedule New Trip</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Select Bus <span class="text-danger">*</span></label>
                            <select name="bus_id" id="tripBusSelect" class="form-select" required>
                                <option value="">-- Choose Bus --</option>
                                <?php foreach ($activeBuses as $b): ?>
                                    <option value="<?= $b['id'] ?>" data-rate="<?= $b['default_rate_per_seat'] ?>">
                                        <?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['registration_number']) ?>) - <?= $b['total_capacity'] ?> seats
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Rate Per Seat (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" name="rate_per_seat" id="tripRateInput" class="form-control" value="250.00" min="1" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Trip Date <span class="text-danger">*</span></label>
                            <input type="date" name="trip_date" id="tripDateInput" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Departure Time <span class="text-danger">*</span></label>
                            <input type="time" name="departure_time" id="tripTimeInput" class="form-control" value="07:30" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" id="tripStatusSelect" class="form-select">
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold d-block">Available Pickup Points for this Trip</label>
                        <div class="row g-2 border rounded-3 p-3 bg-light">
                            <?php foreach ($allPickups as $p): ?>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input pickup-check" type="checkbox" name="pickups[]" value="<?= $p['id'] ?>" id="p_check_<?= $p['id'] ?>" checked>
                                        <label class="form-check-label small" for="p_check_<?= $p['id'] ?>">
                                            <?= htmlspecialchars($p['name']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="tripSubmitBtn">Schedule Trip</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-fill default rate when bus selected
document.getElementById('tripBusSelect')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const defRate = selected.getAttribute('data-rate');
    if (defRate) {
        document.getElementById('tripRateInput').value = defRate;
    }
});

function openEditTripModal(trip) {
    document.getElementById('tripModalLabel').textContent = 'Edit Trip #' + trip.id;
    document.getElementById('tripFormAction').value = 'update';
    document.getElementById('tripFormId').value = trip.id;
    document.getElementById('tripBusSelect').value = trip.bus_id;
    document.getElementById('tripRateInput').value = trip.rate_per_seat;
    document.getElementById('tripDateInput').value = trip.trip_date;
    document.getElementById('tripTimeInput').value = trip.departure_time;
    document.getElementById('tripStatusSelect').value = trip.status;
    document.getElementById('tripSubmitBtn').textContent = 'Update Trip';

    const modal = new bootstrap.Modal(document.getElementById('tripModal'));
    modal.show();
}

// Reset modal on add click
document.querySelector('[data-bs-target="#tripModal"]')?.addEventListener('click', function() {
    document.getElementById('tripModalLabel').textContent = 'Schedule New Trip';
    document.getElementById('tripFormAction').value = 'create';
    document.getElementById('tripFormId').value = '';
    document.getElementById('tripForm').reset();
    document.getElementById('tripSubmitBtn').textContent = 'Schedule Trip';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
