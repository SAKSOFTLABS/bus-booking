<?php
/**
 * Administrator Reports & Analytics Module
 * Comprehensive reports with date-range filters and CSV export.
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();

$reportType = cleanInput($_GET['type'] ?? 'date_range');
$startDate = cleanInput($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
$endDate = cleanInput($_GET['end_date'] ?? date('Y-m-d', strtotime('+7 days')));

// 1. Bus-wise Summary Report Data
$busReport = $db->query("
    SELECT b.id, b.name, b.registration_number, b.total_capacity,
           COUNT(DISTINCT t.id) as trips_count,
           COALESCE(SUM(t.booked_seats), 0) as total_seats_sold,
           COALESCE(SUM(t.total_capacity), 0) as total_capacity_offered,
           COALESCE(SUM(bks.total_amount), 0) as total_revenue
    FROM `buses` b
    LEFT JOIN `trips` t ON t.bus_id = b.id
    LEFT JOIN `bookings` bks ON bks.trip_id = t.id AND bks.booking_status = 'confirmed'
    GROUP BY b.id
    ORDER BY total_revenue DESC
")->fetchAll();

// 2. Trip-wise Occupancy Report Data
$tripReport = $db->prepare("
    SELECT t.*, b.name as bus_name, b.registration_number,
           COALESCE(SUM(bks.total_amount), 0) as revenue_collected,
           ROUND((t.booked_seats / t.total_capacity) * 100, 1) as occupancy_rate
    FROM `trips` t
    JOIN `buses` b ON t.bus_id = b.id
    LEFT JOIN `bookings` bks ON bks.trip_id = t.id AND bks.booking_status = 'confirmed'
    WHERE t.trip_date BETWEEN ? AND ?
    GROUP BY t.id
    ORDER BY t.trip_date DESC, t.departure_time ASC
");
$tripReport->execute([$startDate, $endDate]);
$tripReportRows = $tripReport->fetchAll();

// 3. Payment Status & Cancellation Report Data
$paymentStats = $db->prepare("
    SELECT payment_status, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount
    FROM `bookings`
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY payment_status
");
$paymentStats->execute([$startDate, $endDate]);
$paymentStatsRows = $paymentStats->fetchAll();

// 4. Customer Directory & Booking History Data
$customerReport = $db->query("
    SELECT c.id, c.name, c.phone, c.created_at,
           COUNT(b.id) as total_bookings,
           COALESCE(SUM(b.seats_count), 0) as total_seats,
           COALESCE(SUM(CASE WHEN b.booking_status = 'confirmed' THEN b.total_amount ELSE 0 END), 0) as total_spent
    FROM `customers` c
    LEFT JOIN `bookings` b ON b.customer_phone = c.phone
    GROUP BY c.id
    ORDER BY total_spent DESC LIMIT 50
")->fetchAll();

// 5. Date-Range Bookings Data
$dateRangeStmt = $db->prepare("
    SELECT b.*, t.trip_date, t.departure_time, bs.name as bus_name, p.name as pickup_name
    FROM `bookings` b
    JOIN `trips` t ON b.trip_id = t.id
    JOIN `buses` bs ON t.bus_id = bs.id
    JOIN `pickup_points` p ON b.pickup_point_id = p.id
    WHERE DATE(b.created_at) BETWEEN ? AND ?
    ORDER BY b.id DESC
");
$dateRangeStmt->execute([$startDate, $endDate]);
$dateRangeBookings = $dateRangeStmt->fetchAll();

// Calculate total metrics for date range
$drTotalBookings = count($dateRangeBookings);
$drConfirmed = array_filter($dateRangeBookings, fn($r) => $r['booking_status'] === 'confirmed');
$drTotalSeats = array_sum(array_column($drConfirmed, 'seats_count'));
$drTotalRevenue = array_sum(array_column($drConfirmed, 'total_amount'));
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h3 class="fw-extrabold text-navy mb-1">Reports & Analytics</h3>
        <p class="text-muted small mb-0">Exportable performance reports, occupancy analysis, and revenue breakdowns.</p>
    </div>
</div>

<!-- Date-Range Filter Header -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="type" value="<?= htmlspecialchars($reportType) ?>">
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-0">From Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-0">To Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2 pt-2 pt-md-0">
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">Apply Date Filter</button>
                <a href="?type=<?= htmlspecialchars($reportType) ?>&start_date=<?= date('Y-m-d', strtotime('-30 days')) ?>&end_date=<?= date('Y-m-d', strtotime('+7 days')) ?>" class="btn btn-light btn-sm rounded-pill border">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Report Navigation Tabs -->
<ul class="nav nav-pills gap-2 mb-4">
    <li class="nav-item">
        <a class="nav-link <?= ($reportType === 'date_range') ? 'active' : 'bg-white border text-dark' ?> rounded-pill px-3" href="?type=date_range&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>">
            <i class="bi bi-calendar-range me-1"></i> Date-Range Bookings
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($reportType === 'occupancy') ? 'active' : 'bg-white border text-dark' ?> rounded-pill px-3" href="?type=occupancy&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>">
            <i class="bi bi-pie-chart me-1"></i> Trip Occupancy (70% Alerts)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($reportType === 'buses') ? 'active' : 'bg-white border text-dark' ?> rounded-pill px-3" href="?type=buses&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>">
            <i class="bi bi-bus-front me-1"></i> Bus-wise Performance
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($reportType === 'payments') ? 'active' : 'bg-white border text-dark' ?> rounded-pill px-3" href="?type=payments&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>">
            <i class="bi bi-credit-card me-1"></i> Payments & Cancellations
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($reportType === 'customers') ? 'active' : 'bg-white border text-dark' ?> rounded-pill px-3" href="?type=customers&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>">
            <i class="bi bi-people me-1"></i> Customer History
        </a>
    </li>
</ul>

<!-- ================= TAB 1: DATE-RANGE BOOKINGS ================= -->
<?php if ($reportType === 'date_range'): ?>
    <!-- Summary KPIs for selected range -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 bg-white rounded-4 border shadow-sm">
                <span class="text-muted small d-block">Range Confirmed Revenue</span>
                <h4 class="fw-extrabold text-success mb-0"><?= formatCurrency($drTotalRevenue) ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-white rounded-4 border shadow-sm">
                <span class="text-muted small d-block">Seats Booked</span>
                <h4 class="fw-extrabold text-primary mb-0"><?= number_format($drTotalSeats) ?> Seats</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-white rounded-4 border shadow-sm">
                <span class="text-muted small d-block">Total Transactions</span>
                <h4 class="fw-extrabold text-dark mb-0"><?= number_format($drTotalBookings) ?> Bookings</h4>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Date-Range Sales Log (<?= formatDate($startDate) ?> to <?= formatDate($endDate) ?>)</h5>
            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="exportTableToCSV('dateRangeTable', 'date_range_sales.csv')">
                <i class="bi bi-download me-1"></i> Export to CSV
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="dateRangeTable">
                    <thead class="table-light">
                        <tr>
                            <th>Ref</th>
                            <th>Date Booked</th>
                            <th>Passenger</th>
                            <th>Phone</th>
                            <th>Trip Date</th>
                            <th>Bus</th>
                            <th>Pickup</th>
                            <th>Seats</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dateRangeBookings)): ?>
                            <tr><td colspan="11" class="text-center py-4 text-muted">No bookings found in this date range.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dateRangeBookings as $b): ?>
                                <tr>
                                    <td><strong class="font-monospace text-primary"><?= htmlspecialchars($b['booking_ref']) ?></strong></td>
                                    <td><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></td>
                                    <td><strong><?= htmlspecialchars($b['customer_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($b['customer_phone']) ?></td>
                                    <td><?= formatDate($b['trip_date']) ?> (<?= formatTime($b['departure_time']) ?>)</td>
                                    <td><?= htmlspecialchars($b['bus_name']) ?></td>
                                    <td><?= htmlspecialchars($b['pickup_name']) ?></td>
                                    <td><span class="badge bg-dark"><?= $b['seats_count'] ?></span></td>
                                    <td><strong class="text-success"><?= formatCurrency($b['total_amount']) ?></strong></td>
                                    <td><span class="badge bg-light text-dark border"><?= ucfirst($b['payment_status']) ?></span></td>
                                    <td><span class="badge <?= ($b['booking_status'] === 'confirmed') ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($b['booking_status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- ================= TAB 2: TRIP OCCUPANCY & 70% ALERTS ================= -->
<?php elseif ($reportType === 'occupancy'): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0">Trip-wise Occupancy & 70% Threshold Monitor</h5>
                <span class="text-muted small">Formula: (Booked Seats ÷ Total Capacity) × 100</span>
            </div>
            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="exportTableToCSV('occupancyTable', 'trip_occupancy_report.csv')">
                <i class="bi bi-download me-1"></i> Export to CSV
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="occupancyTable">
                    <thead class="table-light">
                        <tr>
                            <th>Trip ID</th>
                            <th>Trip Date</th>
                            <th>Departure</th>
                            <th>Bus & Reg No.</th>
                            <th>Total Capacity</th>
                            <th>Booked Seats</th>
                            <th>Remaining Seats</th>
                            <th>Occupancy %</th>
                            <th>70% Alert Status</th>
                            <th>Revenue Collected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tripReportRows)): ?>
                            <tr><td colspan="10" class="text-center py-4 text-muted">No trips recorded in this period.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tripReportRows as $tr): 
                                $rem = max(0, $tr['total_capacity'] - $tr['booked_seats']);
                                $occ = $tr['occupancy_rate'];
                                $is70 = ($occ >= 70);
                            ?>
                                <tr class="<?= $is70 ? 'table-warning bg-opacity-25' : '' ?>">
                                    <td><strong class="font-monospace">#<?= $tr['id'] ?></strong></td>
                                    <td><strong><?= formatDate($tr['trip_date']) ?></strong></td>
                                    <td><?= formatTime($tr['departure_time']) ?></td>
                                    <td><?= htmlspecialchars($tr['bus_name']) ?> (<?= htmlspecialchars($tr['registration_number']) ?>)</td>
                                    <td><?= $tr['total_capacity'] ?></td>
                                    <td><strong><?= $tr['booked_seats'] ?></strong></td>
                                    <td><?= $rem ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="occupancy-progress flex-grow-1" style="width: 80px;">
                                                <div class="progress-bar <?= $is70 ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= min(100, $occ) ?>%"></div>
                                            </div>
                                            <strong class="<?= $is70 ? 'text-danger' : 'text-primary' ?>"><?= $occ ?>%</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($tr['occupancy_alert_sent']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check2-all me-1"></i> Alert Dispatched</span>
                                        <?php elseif ($is70): ?>
                                            <span class="badge bg-danger"><i class="bi bi-exclamation-circle me-1"></i> Threshold Reached</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">&lt; 70%</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong class="text-success"><?= formatCurrency($tr['revenue_collected']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- ================= TAB 3: BUS-WISE PERFORMANCE ================= -->
<?php elseif ($reportType === 'buses'): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Bus Fleet Performance & Cumulative Revenue</h5>
            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="exportTableToCSV('busReportTable', 'bus_performance.csv')">
                <i class="bi bi-download me-1"></i> Export to CSV
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="busReportTable">
                    <thead class="table-light">
                        <tr>
                            <th>Bus Name</th>
                            <th>Registration</th>
                            <th>Capacity / Trip</th>
                            <th>Total Scheduled Trips</th>
                            <th>Seats Booked</th>
                            <th>Capacity Offered</th>
                            <th>Fleet Occupancy</th>
                            <th>Total Revenue Generated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($busReport as $br): 
                            $fleetOcc = calculateOccupancy($br['total_seats_sold'], $br['total_capacity_offered']);
                        ?>
                            <tr>
                                <td><strong class="text-navy"><?= htmlspecialchars($br['name']) ?></strong></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($br['registration_number']) ?></span></td>
                                <td><?= $br['total_capacity'] ?> Seats</td>
                                <td><?= $br['trips_count'] ?> Trip(s)</td>
                                <td><strong class="text-primary"><?= number_format($br['total_seats_sold']) ?></strong></td>
                                <td><?= number_format($br['total_capacity_offered']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="occupancy-progress flex-grow-1" style="width: 80px;">
                                            <div class="progress-bar <?= ($fleetOcc >= 70) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= min(100, $fleetOcc) ?>%"></div>
                                        </div>
                                        <strong><?= $fleetOcc ?>%</strong>
                                    </div>
                                </td>
                                <td><h6 class="fw-bold text-success mb-0"><?= formatCurrency($br['total_revenue']) ?></h6></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- ================= TAB 4: PAYMENTS & CANCELLATIONS ================= -->
<?php elseif ($reportType === 'payments'): ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0">Payment Summary (<?= formatDate($startDate) ?> - <?= formatDate($endDate) ?>)</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($paymentStatsRows as $ps): 
                            $badgeClass = ($ps['payment_status'] === 'paid') ? 'bg-success' : (($ps['payment_status'] === 'refunded') ? 'bg-info' : 'bg-danger');
                        ?>
                            <div class="p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge <?= $badgeClass ?> text-uppercase mb-1"><?= $ps['payment_status'] ?></span>
                                    <div class="small text-muted"><?= $ps['count'] ?> Transaction(s)</div>
                                </div>
                                <h5 class="fw-bold mb-0"><?= formatCurrency($ps['amount']) ?></h5>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Cancellation & Refund Logs</h5>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="exportTableToCSV('refundTable', 'refund_cancellations.csv')">Export CSV</button>
                </div>
                <div class="card-body p-4">
                    <?php
                    $cancels = $db->query("
                        SELECT b.*, t.trip_date, bs.name as bus_name
                        FROM `bookings` b
                        JOIN `trips` t ON b.trip_id = t.id
                        JOIN `buses` bs ON t.bus_id = bs.id
                        WHERE b.booking_status = 'cancelled'
                        ORDER BY b.cancelled_at DESC LIMIT 20
                    ")->fetchAll();
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="refundTable">
                            <thead class="table-light small">
                                <tr>
                                    <th>Ref</th>
                                    <th>Passenger</th>
                                    <th>Trip Date</th>
                                    <th>Seats</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php if (empty($cancels)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No cancellations recorded.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($cancels as $c): ?>
                                        <tr>
                                            <td><strong class="font-monospace"><?= htmlspecialchars($c['booking_ref']) ?></strong></td>
                                            <td><?= htmlspecialchars($c['customer_name']) ?></td>
                                            <td><?= formatDate($c['trip_date']) ?></td>
                                            <td><?= $c['seats_count'] ?></td>
                                            <td><strong class="text-danger"><?= formatCurrency($c['total_amount']) ?></strong></td>
                                            <td><span class="small text-muted"><?= htmlspecialchars($c['cancellation_reason'] ?? 'Not specified') ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- ================= TAB 5: CUSTOMER DIRECTORY ================= -->
<?php elseif ($reportType === 'customers'): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Customer Booking History Directory</h5>
            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="exportTableToCSV('custReportTable', 'customers_report.csv')">
                <i class="bi bi-download me-1"></i> Export to CSV
            </button>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="custReportTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Customer Name</th>
                            <th>Mobile Number</th>
                            <th>Registered Since</th>
                            <th>Total Bookings</th>
                            <th>Seats Reserved</th>
                            <th>Cumulative Lifetime Spend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerReport as $cust): ?>
                            <tr>
                                <td><?= $cust['id'] ?></td>
                                <td><strong><?= htmlspecialchars($cust['name']) ?></strong></td>
                                <td><?= htmlspecialchars($cust['phone']) ?></td>
                                <td><?= date('d M Y', strtotime($cust['created_at'])) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= $cust['total_bookings'] ?></span></td>
                                <td><span class="badge bg-primary"><?= $cust['total_seats'] ?> Seats</span></td>
                                <td><strong class="text-success"><?= formatCurrency($cust['total_spent']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
