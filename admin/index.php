<?php
/**
 * Administrator Dashboard & Operations Overview
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();

// 1. Key Metrics Calculations
// Total Bookings
$totalBookings = $db->query("SELECT COUNT(*) FROM `bookings` WHERE `booking_status` = 'confirmed'")->fetchColumn() ?: 0;

// Total Seats Booked
$totalSeatsBooked = $db->query("SELECT SUM(`seats_count`) FROM `bookings` WHERE `booking_status` = 'confirmed'")->fetchColumn() ?: 0;

// Total Revenue
$totalRevenue = $db->query("SELECT SUM(`total_amount`) FROM `bookings` WHERE `payment_status` = 'paid'")->fetchColumn() ?: 0.00;

// Active Buses
$activeBusesCount = $db->query("SELECT COUNT(*) FROM `buses` WHERE `is_active` = 1")->fetchColumn() ?: 0;

// Upcoming & Today's Scheduled Capacity & Bookings
$capacityStats = $db->query("
    SELECT SUM(total_capacity) as total_cap, SUM(booked_seats) as total_booked, COUNT(*) as trip_count
    FROM `trips` 
    WHERE trip_date >= CURDATE() AND status = 'scheduled'
")->fetch();

$upcomingCapacity = (int)($capacityStats['total_cap'] ?? 0);
$upcomingBooked = (int)($capacityStats['total_booked'] ?? 0);
$remainingCapacity = max(0, $upcomingCapacity - $upcomingBooked);
$overallUpcomingOccupancy = calculateOccupancy($upcomingBooked, $upcomingCapacity);

// 2. Fetch Trips at or above 70% Occupancy (Alert Threshold)
$threshold = (float)($settings['occupancy_threshold'] ?? 70.0);
$occupancyAlertTrips = $db->query("
    SELECT t.*, b.name as bus_name, b.registration_number,
           ROUND((t.booked_seats / t.total_capacity) * 100, 1) as occupancy_rate
    FROM `trips` t
    JOIN `buses` b ON t.bus_id = b.id
    WHERE t.status = 'scheduled' AND ((t.booked_seats / t.total_capacity) * 100) >= {$threshold}
    ORDER BY occupancy_rate DESC, t.trip_date ASC
")->fetchAll();

// 3. Fetch Recent Bookings
$recentBookings = $db->query("
    SELECT b.*, t.trip_date, t.departure_time, bs.name as bus_name, p.name as pickup_name
    FROM `bookings` b
    JOIN `trips` t ON b.trip_id = t.id
    JOIN `buses` bs ON t.bus_id = bs.id
    JOIN `pickup_points` p ON b.pickup_point_id = p.id
    ORDER BY b.id DESC LIMIT 8
")->fetchAll();

// 4. Today's Trips
$todayTrips = $db->query("
    SELECT t.*, b.name as bus_name, b.registration_number
    FROM `trips` t
    JOIN `buses` b ON t.bus_id = b.id
    WHERE t.trip_date = CURDATE() AND t.status = 'scheduled'
    ORDER BY t.departure_time ASC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h3 class="fw-extrabold text-navy mb-1">Administrator Dashboard</h3>
        <p class="text-muted small mb-0">Overview of bus schedules, capacity tracking, collections, and occupancy alerts.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/trips.php?action=new" class="btn btn-primary rounded-pill px-3 btn-sm fw-bold">
            <i class="bi bi-calendar-plus me-1"></i> Schedule New Trip
        </a>
        <a href="<?= BASE_URL ?>/admin/buses.php?action=new" class="btn btn-outline-dark rounded-pill px-3 btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Add Bus
        </a>
        <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-outline-primary rounded-pill px-3 btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Reports & Export
        </a>
    </div>
</div>

<!-- Key Performance Indicators (KPIs) -->
<div class="row g-3 mb-4">
    <!-- Total Revenue -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Revenue</span>
                <h3 class="fw-extrabold text-success mb-0"><?= formatCurrency($totalRevenue) ?></h3>
                <span class="small text-muted">Paid Collections</span>
            </div>
            <div class="kpi-icon bg-success-subtle text-success">
                <i class="bi bi-cash-stack"></i>
            </div>
        </div>
    </div>

    <!-- Total Seats Booked -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Seats Booked</span>
                <h3 class="fw-extrabold text-primary mb-0"><?= number_format($totalSeatsBooked) ?></h3>
                <span class="small text-muted">Confirmed Reservations</span>
            </div>
            <div class="kpi-icon bg-primary-subtle text-primary">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>

    <!-- Available Seat Capacity -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Available Capacity</span>
                <h3 class="fw-extrabold text-info mb-0"><?= number_format($remainingCapacity) ?></h3>
                <span class="small text-muted">Upcoming Open Seats</span>
            </div>
            <div class="kpi-icon bg-info-subtle text-info">
                <i class="bi bi-pie-chart-fill"></i>
            </div>
        </div>
    </div>

    <!-- Total Confirmed Bookings -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Bookings</span>
                <h3 class="fw-extrabold text-navy mb-0"><?= number_format($totalBookings) ?></h3>
                <span class="small text-muted">Active Customer Passes</span>
            </div>
            <div class="kpi-icon bg-secondary-subtle text-dark">
                <i class="bi bi-ticket-perforated-fill"></i>
            </div>
        </div>
    </div>
</div>

<!-- ================= 70% SEAT OCCUPANCY ALERTS SECTION ================= -->
<div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-danger">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-danger-subtle text-danger rounded-3">
                <i class="bi bi-bell-fill fs-5"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-0 text-danger">70% Seat Occupancy Alert Monitor</h5>
                <p class="text-muted small mb-0">Automatic threshold calculation: <code>(Booked Seats ÷ Bus Capacity) × 100 ≥ <?= $threshold ?>%</code></p>
            </div>
        </div>
        <span class="badge bg-danger px-3 py-2 rounded-pill">
            <?= count($occupancyAlertTrips) ?> Trip(s) At / Above Threshold
        </span>
    </div>

    <div class="card-body p-4">
        <?php if (empty($occupancyAlertTrips)): ?>
            <div class="p-3 bg-light rounded-3 text-center text-muted small">
                <i class="bi bi-shield-check text-success fs-5 me-1"></i>
                All active trips are currently operating under the <?= $threshold ?>% capacity threshold. No critical congestion alerts active.
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($occupancyAlertTrips as $altTrip): 
                    $rem = max(0, $altTrip['total_capacity'] - $altTrip['booked_seats']);
                ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="p-3 bg-danger bg-opacity-10 rounded-3 border border-danger">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong class="text-navy d-block"><?= htmlspecialchars($altTrip['bus_name']) ?></strong>
                                    <span class="badge bg-dark small"><?= htmlspecialchars($altTrip['registration_number']) ?></span>
                                </div>
                                <span class="badge bg-danger fs-6"><?= $altTrip['occupancy_rate'] ?>% Occupied</span>
                            </div>

                            <div class="small text-muted mb-2">
                                <div><i class="bi bi-calendar3 me-1"></i> <?= formatDate($altTrip['trip_date']) ?> at <?= formatTime($altTrip['departure_time']) ?></div>
                                <div><i class="bi bi-person-fill-lock me-1"></i> Booked: <strong><?= $altTrip['booked_seats'] ?></strong> / <?= $altTrip['total_capacity'] ?> (<?= $rem ?> seats left)</div>
                            </div>

                            <div class="occupancy-progress mb-2">
                                <div class="progress-bar bg-danger" style="width: <?= min(100, $altTrip['occupancy_rate']) ?>%"></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="text-success fw-bold">
                                    <i class="bi bi-check-circle-fill me-1"></i> Admin Alert Sent
                                </span>
                                <a href="<?= BASE_URL ?>/admin/trips.php?id=<?= $altTrip['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill">
                                    Manage Trip
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Middle Row: Today's Trips & Recent Bookings -->
<div class="row g-4 mb-4">
    <!-- Today's Trips Schedules -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-calendar-event me-2 text-primary"></i> Today's Trips</h5>
                <span class="badge bg-light text-dark border"><?= date('D, d M Y') ?></span>
            </div>
            <div class="card-body p-4">
                <?php if (empty($todayTrips)): ?>
                    <p class="text-muted small text-center my-4">No trips scheduled for today.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($todayTrips as $t): 
                            $occ = calculateOccupancy($t['booked_seats'], $t['total_capacity']);
                            $avail = max(0, $t['total_capacity'] - $t['booked_seats']);
                        ?>
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-navy"><?= htmlspecialchars($t['bus_name']) ?></strong>
                                    <span class="badge bg-primary"><?= formatTime($t['departure_time']) ?></span>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mb-2">
                                    <span><?= $t['booked_seats'] ?> / <?= $t['total_capacity'] ?> Booked</span>
                                    <span class="fw-bold <?= ($occ >= 70) ? 'text-danger' : 'text-primary' ?>"><?= $occ ?>% Occupancy</span>
                                </div>
                                <div class="occupancy-progress mb-2">
                                    <div class="progress-bar <?= ($occ >= 70) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= min(100, $occ) ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center small">
                                    <span class="text-muted">Rate: <?= formatCurrency($t['rate_per_seat']) ?></span>
                                    <span class="badge bg-success-subtle text-success border border-success"><?= $avail ?> Seats Left</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Bookings Table -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i> Recent Bookings</h5>
                <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-outline-primary btn-sm rounded-pill">View All</a>
            </div>
            <div class="card-body p-4">
                <?php if (empty($recentBookings)): ?>
                    <p class="text-muted small text-center my-4">No recent bookings recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Ref</th>
                                    <th>Passenger</th>
                                    <th>Trip & Date</th>
                                    <th>Seats</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php foreach ($recentBookings as $rb): ?>
                                    <tr>
                                        <td><strong class="font-monospace text-primary"><?= htmlspecialchars($rb['booking_ref']) ?></strong></td>
                                        <td>
                                            <strong><?= htmlspecialchars($rb['customer_name']) ?></strong>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($rb['customer_phone']) ?></div>
                                        </td>
                                        <td>
                                            <span><?= formatDate($rb['trip_date'], 'd M') ?></span>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($rb['bus_name']) ?></div>
                                        </td>
                                        <td><span class="badge bg-dark"><?= $rb['seats_count'] ?></span></td>
                                        <td><strong class="text-success"><?= formatCurrency($rb['total_amount']) ?></strong></td>
                                        <td>
                                            <?php if ($rb['booking_status'] === 'confirmed'): ?>
                                                <span class="badge bg-success-subtle text-success">Paid</span>
                                            <?php elseif ($rb['booking_status'] === 'cancelled'): ?>
                                                <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning">Pending</span>
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
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
