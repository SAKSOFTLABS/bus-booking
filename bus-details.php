<?php
/**
 * Bus & Trip Details Page (BookMyShow Movie/Showtime Style)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$busId = (int)($_GET['id'] ?? 0);
if (!$busId) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$db = getDB();
$settings = getSystemSettings();
$customer = AuthService::getCustomer();

// Fetch Bus Details
$stmt = $db->prepare("SELECT * FROM `buses` WHERE id = ? AND is_active = 1");
$stmt->execute([$busId]);
$bus = $stmt->fetch();

if (!$bus) {
    die("Bus not found or inactive.");
}

// Selected Date Filter (default today)
$selectedDate = cleanInput($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Fetch scheduled trips for this bus on selected date
$tripStmt = $db->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM `trip_pickup_points` tpp WHERE tpp.trip_id = t.id) as pickups_count
    FROM `trips` t
    WHERE t.bus_id = ? AND t.trip_date = ? AND t.status = 'scheduled'
    ORDER BY t.departure_time ASC
");
$tripStmt->execute([$busId, $selectedDate]);
$trips = $tripStmt->fetchAll();

// Fetch Pickup Points mapped to these trips
$pickupMap = [];
if (!empty($trips)) {
    $tripIds = array_column($trips, 'id');
    $placeholders = implode(',', array_fill(0, count($tripIds), '?'));
    $pStmt = $db->prepare("
        SELECT tpp.trip_id, p.name as pickup_name, p.landmark, tpp.pickup_time
        FROM `trip_pickup_points` tpp
        JOIN `pickup_points` p ON tpp.pickup_point_id = p.id
        WHERE tpp.trip_id IN ($placeholders)
        ORDER BY p.display_order ASC
    ");
    $pStmt->execute($tripIds);
    while ($row = $pStmt->fetch()) {
        $pickupMap[$row['trip_id']][] = $row;
    }
}

$busImg = !empty($bus['image_url']) ? $bus['image_url'] : 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80';
$amenities = explode(',', $bus['amenities'] ?? 'AC, Devotional Audio, Pushback Seats, Water Bottle');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Bus Header Banner (BookMyShow Movie Header Style) -->
<div class="py-4 bg-maroon text-white border-bottom border-warning">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item text-white-50">Yatra Buses</li>
                <li class="breadcrumb-item active text-white" aria-current="page"><?= htmlspecialchars($bus['name']) ?></li>
            </ol>
        </nav>

        <div class="row align-items-center g-4">
            <div class="col-md-4 col-lg-3">
                <div class="rounded-4 overflow-hidden border border-2 border-warning shadow-lg" style="height: 220px;">
                    <img src="<?= htmlspecialchars($busImg) ?>" alt="<?= htmlspecialchars($bus['name']) ?>" class="w-100 h-100 object-fit-cover">
                </div>
            </div>

            <div class="col-md-8 col-lg-9">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark fw-bold">★ <?= $bus['rating'] ?? 4.8 ?> / 5 Devotee Rating</span>
                    <span class="badge bg-danger text-white"><?= htmlspecialchars($bus['bus_type'] ?? '2+2 Pushback AC') ?></span>
                    <span class="badge bg-light text-dark font-monospace"><?= htmlspecialchars($bus['registration_number']) ?></span>
                </div>

                <h2 class="fw-extrabold text-white mb-2">
                    <?= htmlspecialchars($bus['name']) ?>
                </h2>

                <p class="text-warning mb-2 fw-bold">
                    <i class="bi bi-geo-alt-fill me-1"></i> Destination: <?= htmlspecialchars($settings['destination_name']) ?>
                </p>

                <p class="text-white-50 small mb-3">
                    <i class="bi bi-pin-map text-warning me-1"></i> Arrival Point: <?= htmlspecialchars($settings['destination_landmark']) ?>
                </p>

                <!-- Amenities Badges -->
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($amenities as $am): ?>
                        <span class="badge bg-dark bg-opacity-50 border border-warning text-white small">
                            <i class="bi bi-check2 text-warning me-1"></i> <?= htmlspecialchars(trim($am)) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Showtime & Trip Schedules Section (Like BookMyShow Showtimes Screen) -->
<div class="container my-5">
    
    <!-- Date Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar3 text-danger fs-5"></i>
                <h6 class="fw-bold mb-0 text-navy">Select Yatra Date:</h6>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <?php
                $dates = [
                    date('Y-m-d') => 'Today (' . date('d M') . ')',
                    date('Y-m-d', strtotime('+1 day')) => 'Tomorrow (' . date('d M', strtotime('+1 day')) . ')',
                    date('Y-m-d', strtotime('+2 days')) => date('D, d M', strtotime('+2 days')),
                    date('Y-m-d', strtotime('+3 days')) => date('D, d M', strtotime('+3 days'))
                ];
                foreach ($dates as $d => $lbl):
                    $isActive = ($selectedDate === $d);
                ?>
                    <a href="?id=<?= $bus['id'] ?>&date=<?= $d ?>" class="btn btn-sm <?= $isActive ? 'btn-danger text-white fw-bold shadow-sm' : 'btn-outline-secondary' ?> rounded-pill px-3">
                        <?= $lbl ?>
                    </a>
                <?php endforeach; ?>

                <form method="GET" action="bus-details.php" class="d-inline-flex">
                    <input type="hidden" name="id" value="<?= $bus['id'] ?>">
                    <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" min="<?= date('Y-m-d') ?>" class="form-control form-control-sm rounded-pill" onchange="this.form.submit()">
                </form>
            </div>
        </div>
    </div>

    <!-- Available Trips / Showtimes List -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-extrabold text-navy mb-0">
            Available Trips for <?= formatDate($selectedDate) ?>
        </h4>
        <span class="badge bg-danger text-white px-3 py-2 rounded-pill">
            <?= count($trips) ?> Departure(s) Scheduled
        </span>
    </div>

    <?php if (empty($trips)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <div class="display-5 text-muted mb-3"><i class="bi bi-calendar-x"></i></div>
            <h5 class="fw-bold text-navy">No scheduled trips found for <?= formatDate($selectedDate) ?></h5>
            <p class="text-muted small">This bus is not scheduled on this selected date. Please choose another date above or browse other yatra buses.</p>
            <div class="mt-3">
                <a href="?id=<?= $bus['id'] ?>&date=<?= date('Y-m-d') ?>" class="btn btn-outline-danger rounded-pill px-4 me-2">View Today's Trips</a>
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-secondary rounded-pill px-4">Browse All Buses</a>
            </div>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($trips as $trip): 
                $remainingSeats = max(0, $trip['total_capacity'] - $trip['booked_seats']);
                $occupancyPercent = calculateOccupancy($trip['booked_seats'], $trip['total_capacity']);
                $isSoldOut = ($remainingSeats <= 0);
                $isNearFull = ($occupancyPercent >= 70 && !$isSoldOut);
                $pickups = $pickupMap[$trip['id']] ?? [];
            ?>
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden <?= $isSoldOut ? 'opacity-75' : '' ?>">
                    <div class="card-body p-4">
                        <div class="row align-items-center g-3">
                            <!-- Departure Showtime Badge -->
                            <div class="col-md-3 col-sm-6 text-center text-md-start">
                                <div class="d-inline-flex flex-column align-items-center align-items-md-start">
                                    <span class="text-muted small text-uppercase fw-bold">Departure Time</span>
                                    <div class="bms-timing-badge mt-1">
                                        <i class="bi bi-clock-fill me-1"></i> <?= formatTime($trip['departure_time']) ?>
                                    </div>
                                    <span class="small text-muted mt-1">Direct Temple Service</span>
                                </div>
                            </div>

                            <!-- Seat Capacity & Occupancy Bar -->
                            <div class="col-md-4 col-sm-6">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Seats: <strong><?= $trip['booked_seats'] ?> / <?= $trip['total_capacity'] ?> Booked</strong></span>
                                    <strong class="<?= $isNearFull ? 'text-danger' : 'text-primary' ?>"><?= $occupancyPercent ?>% Full</strong>
                                </div>
                                <div class="occupancy-progress mb-2">
                                    <div class="progress-bar <?= $isNearFull ? 'bg-danger' : ($occupancyPercent > 40 ? 'bg-warning' : 'bg-success') ?>" 
                                         role="progressbar" 
                                         style="width: <?= min(100, $occupancyPercent) ?>%"></div>
                                </div>
                                <div>
                                    <?php if ($isSoldOut): ?>
                                        <span class="badge bg-danger">Sold Out</span>
                                    <?php elseif ($isNearFull): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger">
                                            <i class="bi bi-fire me-1"></i> Filling Fast (≥70%) • <?= $remainingSeats ?> Seats Left
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success">
                                            <i class="bi bi-check-circle me-1"></i> <?= $remainingSeats ?> Seats Available
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Rate Per Seat -->
                            <div class="col-md-2 col-6 text-center text-md-end">
                                <span class="text-muted small d-block">Fare Per Seat</span>
                                <h4 class="fw-extrabold text-navy mb-0"><?= formatCurrency($trip['rate_per_seat']) ?></h4>
                                <small class="text-muted">Taxes included</small>
                            </div>

                            <!-- "Book Seat" Action Button -->
                            <div class="col-md-3 col-6 text-end">
                                <?php if ($isSoldOut): ?>
                                    <button class="btn btn-secondary rounded-pill px-4 fw-bold w-100" disabled>
                                        Housefull
                                    </button>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/book.php?trip_id=<?= $trip['id'] ?>" class="btn btn-devotional rounded-pill px-4 py-2 fw-bold w-100 shadow-sm">
                                        Book Seat <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Pickup Points Stops Accordion / Preview -->
                        <?php if (!empty($pickups)): ?>
                            <div class="mt-3 pt-3 border-top border-light-subtle d-flex flex-wrap align-items-center gap-2 small text-muted">
                                <span class="fw-bold text-dark"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Boarding Stops:</span>
                                <?php foreach ($pickups as $idx => $p): ?>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($p['pickup_name']) ?> <?= !empty($p['pickup_time']) ? '('.formatTime($p['pickup_time']).')' : '' ?>
                                    </span>
                                    <?php if ($idx < count($pickups) - 1): ?>
                                        <i class="bi bi-chevron-right text-muted small"></i>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
