<?php
/**
 * Customer Homepage - TempleYatra Pilgrimage Bus Booking
 * Redesigned to match the devotional mobile/web mockup layout exactly
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$settings = getSystemSettings();
$customer = AuthService::getCustomer();

// Selected filters from query params
$selectedDate = cleanInput($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}
$selectedPickup = (int)($_GET['pickup_id'] ?? 0);
$selectedSeats = max(1, min(10, (int)($_GET['seats'] ?? 1)));

// Fetch all active pickup points for search dropdown
$pickupPoints = $db->query("SELECT * FROM `pickup_points` WHERE is_active = 1 ORDER BY display_order ASC, id ASC")->fetchAll();

// Fetch active buses with trip count and minimum fare for the selected date
$busStmt = $db->prepare("
    SELECT b.*,
           COUNT(t.id) as scheduled_trips_count,
           MIN(t.rate_per_seat) as min_rate,
           COALESCE(SUM(t.total_capacity - t.booked_seats), 0) as total_available_seats
    FROM `buses` b
    LEFT JOIN `trips` t ON t.bus_id = b.id AND t.trip_date = ? AND t.status = 'scheduled'
    WHERE b.is_active = 1
    GROUP BY b.id
    ORDER BY b.id ASC
");
$busStmt->execute([$selectedDate]);
$buses = $busStmt->fetchAll();

// Pre-fetch pickup stops for buses
$busPickupStops = [];
$busPickupStmt = $db->query("
    SELECT t.bus_id, p.name as pickup_name, p.display_order
    FROM trip_pickup_points tpp
    JOIN trips t ON tpp.trip_id = t.id
    JOIN pickup_points p ON tpp.pickup_point_id = p.id
    WHERE p.is_active = 1
    GROUP BY t.bus_id, p.id
    ORDER BY p.display_order ASC
");
while ($row = $busPickupStmt->fetch()) {
    $busPickupStops[$row['bus_id']][] = $row['pickup_name'];
}

// Fetch active hero slider banners from hero_slides table
$heroSlidesStmt = $db->query("SELECT * FROM `hero_slides` WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
$heroSlides = $heroSlidesStmt->fetchAll();
if (empty($heroSlides)) {
    // Fallback single slide
    $heroSlides = [[
        'title' => 'Visit the Divine Abode',
        'subtitle' => 'Comfortable and Safe Bus Travel to Your Favourite Temple',
        'image_url' => BASE_URL . '/assets/images/hero_temple_bg.png',
        'link_url' => '#available-buses',
        'button_text' => 'Book Your Darshan'
    ]];
}

// Find selected pickup name if any
$selectedPickupName = 'Select Pickup Point';
if ($selectedPickup > 0) {
    foreach ($pickupPoints as $p) {
        if ($p['id'] == $selectedPickup) {
            $selectedPickupName = $p['name'];
            break;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ================= 1ST SECTION: HERO SLIDER CAROUSEL BANNER (ADMIN CONTROLLED) ================= -->
<section class="hero-mockup-wrapper">
    <div id="templeHeroCarousel" class="carousel slide carousel-fade hero-mockup-carousel" data-bs-ride="carousel" data-bs-interval="6000">
        
        <!-- Bottom Indicators (3 Dots) -->
        <?php if (count($heroSlides) > 1): ?>
            <div class="carousel-indicators">
                <?php foreach ($heroSlides as $idx => $s): ?>
                    <button type="button" data-bs-target="#templeHeroCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= ($idx === 0) ? 'active' : '' ?>" aria-current="<?= ($idx === 0) ? 'true' : 'false' ?>" aria-label="Slide <?= $idx + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Carousel Slides -->
        <div class="carousel-inner">
            <?php foreach ($heroSlides as $idx => $slide): 
                $sImg = !empty($slide['image_url']) ? $slide['image_url'] : BASE_URL . '/assets/images/hero_temple_bg.png';
                if (!preg_match('#^https?://#', $sImg) && !str_starts_with($sImg, '/')) {
                    $sImg = BASE_URL . '/' . ltrim($sImg, '/');
                }
                $sLink = !empty($slide['link_url']) ? $slide['link_url'] : '#available-buses';
                $sBtn = !empty($slide['button_text']) ? $slide['button_text'] : 'Book Your Darshan';
                $sTitle = !empty($slide['title']) ? $slide['title'] : 'Visit the Divine Abode';
                $sSubtitle = !empty($slide['subtitle']) ? $slide['subtitle'] : 'Comfortable and Safe Bus Travel to Your Favourite Temple';
            ?>
                <div class="carousel-item <?= ($idx === 0) ? 'active' : '' ?>">
                    <!-- Slide Temple Gopuram Image -->
                    <img src="<?= htmlspecialchars($sImg) ?>" class="hero-mockup-bg" alt="<?= htmlspecialchars($sTitle) ?>">

                    <!-- Soft Gradient Overlay -->
                    <div class="hero-mockup-overlay"></div>

                    <!-- Slide Content -->
                    <div class="container h-100">
                        <div class="hero-mockup-content">
                            <!-- Spiritual Journey Sub-badge -->
                            <div class="hero-pretitle">SPIRITUAL JOURNEY</div>

                            <!-- Main Headline -->
                            <h1 class="hero-main-title">
                                <?= htmlspecialchars($sTitle) ?>
                            </h1>

                            <!-- Subtitle Description -->
                            <p class="hero-main-subtitle">
                                <?= htmlspecialchars($sSubtitle) ?>
                            </p>

                            <!-- CTA Button with Right Arrow -->
                            <div>
                                <a href="<?= htmlspecialchars($sLink) ?>" class="btn-hero-darshan">
                                    <?= htmlspecialchars($sBtn) ?> <i class="bi bi-arrow-right fs-6"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Circular Prev / Next Controls -->
        <?php if (count($heroSlides) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#templeHeroCarousel" data-bs-slide="prev" aria-label="Previous">
                <i class="bi bi-chevron-left text-dark"></i>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#templeHeroCarousel" data-bs-slide="next" aria-label="Next">
                <i class="bi bi-chevron-right text-dark"></i>
            </button>
        <?php endif; ?>
    </div>
</section>

<!-- Main Container for Floating Card & Highlights -->
<div class="container">

    <!-- ================= FLOATING SEARCH CARD ("Book Your Temple Trip") ================= -->
    <div class="temple-floating-search-card">
        <!-- Lotus Flower Floral Watermark (Top Right) -->
        <img src="<?= BASE_URL ?>/assets/images/card_lotus_watermark.png" alt="Lotus" class="card-watermark-lotus" onerror="this.style.display='none'">

        <div class="search-card-inner">
            <!-- Header inside card -->
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="search-bus-icon">
                    <i class="bi bi-bus-front"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">Book Your Temple Trip</h5>
                    <small class="text-muted" style="font-size: 0.82rem;">Choose your travel date and pickup point</small>
                </div>
            </div>

            <!-- Search Form -->
            <form method="GET" action="index.php" id="yatraSearchForm">
                <!-- Row 1: Travel Date & Pickup Point -->
                <div class="row g-2">
                    <!-- Left Field: Travel Date -->
                    <div class="col-6">
                        <div class="search-box-field" id="dateFieldWrap">
                            <i class="bi bi-calendar3 field-icon"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="field-label">Travel Date</div>
                                <div class="field-value" id="displayDateText">
                                    <?= date('d M Y', strtotime($selectedDate)) ?>
                                </div>
                            </div>
                            <i class="bi bi-chevron-down text-muted small ms-auto"></i>
                            <!-- Hidden Native Date Picker overlay -->
                            <input type="date" name="date" id="nativeDateInput" value="<?= htmlspecialchars($selectedDate) ?>" min="<?= date('Y-m-d') ?>" class="search-date-overlay">
                        </div>
                    </div>

                    <!-- Right Field: Pickup Point -->
                    <div class="col-6">
                        <div class="search-box-field" id="pickupFieldWrap">
                            <i class="bi bi-geo-alt-fill field-icon text-primary"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="field-label">Pickup Point</div>
                                <div class="field-value" id="displayPickupText">
                                    <?= htmlspecialchars($selectedPickupName) ?>
                                </div>
                            </div>
                            <i class="bi bi-chevron-down text-muted small ms-auto"></i>
                            <!-- Transparent Select Dropdown overlay -->
                            <select name="pickup_id" id="nativePickupSelect" class="search-select-transparent">
                                <option value="0">Select Pickup Point</option>
                                <?php foreach ($pickupPoints as $pt): ?>
                                    <option value="<?= $pt['id'] ?>" <?= ($selectedPickup == $pt['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pt['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Number of Seats -->
                <div class="search-stepper-box">
                    <div class="d-flex align-items-center gap-2">
                        <!-- Seat Icon -->
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#334155" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 18V21H7V18H17V21H20V18C20 16.3431 18.6569 15 17 15H7C5.34315 15 4 16.3431 4 18Z"/>
                            <path d="M6 13V5C6 3.89543 6.89543 3 8 3H16C17.1046 3 18 3.89543 18 5V13H6Z"/>
                        </svg>
                        <span class="fw-bold text-dark" style="font-size: 0.92rem;">Number of Seats</span>
                    </div>

                    <!-- Stepper [-] 1 [+] -->
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="stepper-btn" id="btnMinusSeat" aria-label="Decrease Seats">&minus;</button>
                        <span class="stepper-count" id="displaySeatCount"><?= $selectedSeats ?></span>
                        <button type="button" class="stepper-btn" id="btnPlusSeat" aria-label="Increase Seats">&plus;</button>
                        <input type="hidden" name="seats" id="hiddenSeatInput" value="<?= $selectedSeats ?>">
                    </div>
                </div>

                <!-- Row 3: Full Width Search Button -->
                <div>
                    <button type="submit" class="btn-search-buses">
                        <i class="bi bi-search fs-6"></i>
                        <span>Search Buses</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= FEATURE HIGHLIGHTS (4 CIRCULAR BADGES) ================= -->
    <div class="feature-badges-row">
        <!-- 1: Direct Travel to Temple -->
        <div class="feature-badge-item">
            <div class="feature-circle gold">
                <!-- Temple Gopuram Icon -->
                <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2L15 6H9L12 2Z"/>
                    <path d="M7 6L12 3L17 6V9H7V6Z"/>
                    <path d="M5 9L12 5L19 9V13H5V9Z"/>
                    <path d="M3 13L12 8L21 13V21H15V16H9V21H3V13Z"/>
                </svg>
            </div>
            <div class="feature-label">Direct Travel<br>to Temple</div>
        </div>

        <!-- 2: Safe & Reliable -->
        <div class="feature-badge-item">
            <div class="feature-circle green">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="feature-label">Safe &<br>Reliable</div>
        </div>

        <!-- 3: Comfortable Journey -->
        <div class="feature-badge-item">
            <div class="feature-circle pink">
                <!-- Recliner Seat Icon -->
                <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7 3C5.9 3 5 3.9 5 5V15C5 16.1 5.9 17 7 17H15C16.1 17 17 16.1 17 15V13H9V5H17V3H7Z"/>
                    <path d="M17 15L20 18H10L8 20H22L18 15H17Z"/>
                </svg>
            </div>
            <div class="feature-label">Comfortable<br>Journey</div>
        </div>

        <!-- 4: Family & Group Friendly -->
        <div class="feature-badge-item">
            <div class="feature-circle purple">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="feature-label">Family &<br>Group Friendly</div>
        </div>
    </div>

    <!-- ================= 2ND SECTION: AVAILABLE BUSES ================= -->
    <section id="available-buses" class="my-4">
        <!-- Section Header -->
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h3 class="section-title-available">
                Available <span class="text-orange-bus">Buses</span>
            </h3>
            <span class="buses-count-badge">
                <?= count($buses) ?> Buses Found
            </span>
        </div>
        <p class="text-muted small mb-3">
            Select a bus and book your seats for a peaceful journey
        </p>

        <!-- Horizontal Bus Cards List -->
        <?php if (empty($buses)): ?>
            <div class="card border-0 rounded-4 p-5 text-center bg-white shadow-sm my-4">
                <div class="display-6 text-muted mb-2"><i class="bi bi-bus-front"></i></div>
                <h5 class="fw-bold text-dark">No Buses Available</h5>
                <p class="text-muted small">No active buses match your query. Please change your travel date.</p>
                <div>
                    <a href="index.php" class="btn btn-sm btn-outline-danger rounded-pill px-4">Reset Search</a>
                </div>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($buses as $bus): 
                    $busImg = !empty($bus['image_url']) ? $bus['image_url'] : BASE_URL . '/assets/images/bus1_mockup.png';
                    if (!preg_match('#^https?://#', $busImg) && !str_starts_with($busImg, '/')) {
                        $busImg = BASE_URL . '/' . ltrim($busImg, '/');
                    }
                    $busRate = $bus['min_rate'] ? (float)$bus['min_rate'] : (float)$bus['default_rate_per_seat'];
                    $busType = $bus['bus_type'] ?? 'AC';
                    $isAC = (stripos($busType, 'AC') !== false && stripos($busType, 'Non-AC') === false);
                    $capacity = $bus['total_capacity'] ?? 45;

                    // Pickup points for this bus
                    $stops = $busPickupStops[$bus['id']] ?? ['Thrissur', 'Guruvayur', 'Wadakkanchery', 'Others'];
                    $stopsDisplay = implode(' | ', array_slice($stops, 0, 3));
                    if (count($stops) > 3) {
                        $stopsDisplay .= ' | Others';
                    }

                    // Maintenance / Driver highlight
                    $badgeHighlight = ($bus['id'] % 2 == 1) ? 'Well Maintained' : 'Experienced Driver';
                    if (!empty($bus['amenities'])) {
                        $amArray = explode(',', $bus['amenities']);
                        foreach ($amArray as $am) {
                            $amClean = trim($am);
                            if (stripos($amClean, 'Maintained') !== false || stripos($amClean, 'Driver') !== false || stripos($amClean, 'Sanitized') !== false) {
                                $badgeHighlight = $amClean;
                                break;
                            }
                        }
                    }
                ?>
                    <div class="horizontal-bus-card">
                        <!-- Left: Bus Photo -->
                        <div class="bus-img-col">
                            <img src="<?= htmlspecialchars($busImg) ?>" alt="<?= htmlspecialchars($bus['name']) ?>" loading="lazy" onerror="this.src='<?= BASE_URL ?>/assets/images/bus1_mockup.png'">
                        </div>

                        <!-- Right: Details -->
                        <div class="bus-info-col">
                            <!-- Top: Bus Title & Fare -->
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <h4 class="bus-name-title">
                                        <a href="<?= BASE_URL ?>/bus-details.php?id=<?= $bus['id'] ?>&date=<?= $selectedDate ?>&seats=<?= $selectedSeats ?>&pickup_id=<?= $selectedPickup ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($bus['name']) ?>
                                        </a>
                                    </h4>
                                </div>
                                <div class="bus-rate-display">
                                    <div class="bus-rate-amount">₹ <?= number_format($busRate, 0) ?></div>
                                    <div class="bus-rate-unit">per seat</div>
                                </div>
                            </div>

                            <!-- Badges Row -->
                            <div class="bus-specs-row">
                                <!-- Seats -->
                                <div class="d-inline-flex align-items-center gap-1">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#9a3412">
                                        <path d="M4 18V21H7V18H17V21H20V18C20 16.3431 18.6569 15 17 15H7C5.34315 15 4 16.3431 4 18Z"/>
                                        <path d="M6 13V5C6 3.89543 6.89543 3 8 3H16C17.1046 3 18 3.89543 18 5V13H6Z"/>
                                    </svg>
                                    <span><?= $capacity ?> Seats</span>
                                </div>

                                <!-- AC / Non-AC -->
                                <div class="d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-snow text-info"></i>
                                    <span><?= $isAC ? 'AC' : 'Non-AC' ?></span>
                                </div>

                                <!-- Rating / Highlight -->
                                <div class="d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-star-fill text-warning"></i>
                                    <span><?= htmlspecialchars($badgeHighlight) ?></span>
                                </div>
                            </div>

                            <!-- Pickup Points Row -->
                            <div class="bus-pickups-row">
                                <i class="bi bi-geo-alt-fill text-dark me-1"></i>
                                <strong class="text-dark">Pickup Points</strong>
                                <div class="text-muted small ps-3">
                                    <?= htmlspecialchars($stopsDisplay) ?>
                                </div>
                            </div>

                            <!-- Bottom Row: Direct to Temple badge & Book Now Button -->
                            <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                                <span class="badge-direct-temple">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2L15 6H9L12 2Z"/>
                                        <path d="M5 9L12 5L19 9V13H5V9Z"/>
                                        <path d="M3 13L12 8L21 13V21H15V16H9V21H3V13Z"/>
                                    </svg>
                                    Direct to Temple
                                </span>

                                <a href="<?= BASE_URL ?>/bus-details.php?id=<?= $bus['id'] ?>&date=<?= $selectedDate ?>&seats=<?= $selectedSeats ?>&pickup_id=<?= $selectedPickup ?>" class="btn-book-now-pill">
                                    Book Now <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Interactive Search Card JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Date Picker Binding
    const dateInput = document.getElementById('nativeDateInput');
    const displayDate = document.getElementById('displayDateText');
    if (dateInput && displayDate) {
        dateInput.addEventListener('change', function() {
            if (this.value) {
                const parts = this.value.split('-');
                if (parts.length === 3) {
                    const dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
                    const formatted = dateObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                    displayDate.textContent = formatted;
                }
            }
        });
    }

    // 2. Pickup Select Binding
    const pickupSelect = document.getElementById('nativePickupSelect');
    const displayPickup = document.getElementById('displayPickupText');
    if (pickupSelect && displayPickup) {
        pickupSelect.addEventListener('change', function() {
            const selectedOpt = this.options[this.selectedIndex];
            displayPickup.textContent = selectedOpt ? selectedOpt.text : 'Select Pickup Point';
        });
    }

    // 3. Stepper Binding
    const btnMinus = document.getElementById('btnMinusSeat');
    const btnPlus = document.getElementById('btnPlusSeat');
    const displaySeats = document.getElementById('displaySeatCount');
    const hiddenSeats = document.getElementById('hiddenSeatInput');

    if (btnMinus && btnPlus && displaySeats && hiddenSeats) {
        btnMinus.addEventListener('click', function(e) {
            e.preventDefault();
            let val = parseInt(hiddenSeats.value) || 1;
            if (val > 1) {
                val--;
                hiddenSeats.value = val;
                displaySeats.textContent = val;
            }
        });

        btnPlus.addEventListener('click', function(e) {
            e.preventDefault();
            let val = parseInt(hiddenSeats.value) || 1;
            if (val < 10) {
                val++;
                hiddenSeats.value = val;
                displaySeats.textContent = val;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
