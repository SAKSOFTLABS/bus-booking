<?php
/**
 * Customer Public Header - Hindu Devotional Pilgrimage Theme
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$settings = getSystemSettings();
$customer = AuthService::getCustomer();
$destName = $settings['destination_name'] ?? 'Predefined Temple Destination';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Sacred Temple Pilgrimage Yatra</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Rozha+One&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Devotional Theme CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const CURRENCY_SYMBOL = '<?= CURRENCY_SYMBOL ?>';
    </script>
</head>
<body>

<!-- Devotional Top Banner Bar -->
<div class="top-announcement-bar py-1 px-3">
    <div class="container d-flex justify-content-between align-items-center small flex-wrap gap-2">
        <div>
            <span class="text-warning fw-bold fs-6">ॐ</span>
            <strong>Sacred Destination:</strong> <?= htmlspecialchars($destName) ?>
        </div>
        <div class="d-none d-md-flex gap-3 align-items-center">
            <span><i class="bi bi-clock-history me-1 text-warning"></i> Darshan Timely Arrival</span>
            <span><i class="bi bi-telephone-fill me-1 text-warning"></i> Yatra Helpline: <?= htmlspecialchars($settings['destination_contact'] ?? '+91 94470 10808') ?></span>
        </div>
    </div>
</div>

<!-- Main Navigation Bar (TempleYatra Brand & Layout) -->
<nav class="navbar navbar-expand-lg temple-header-bar sticky-top shadow-sm py-2">
    <div class="container">
        <!-- Brand Logo & Title -->
        <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none py-1" href="<?= BASE_URL ?>/index.php">
            <img src="<?= BASE_URL ?>/assets/images/temple_logo.png" alt="Temple" class="temple-logo-mark" onerror="this.outerHTML='<span class=\'fs-3 text-warning\'>🛕</span>'">
            <div>
                <span class="brand-title-temple d-block">TempleYatra</span>
                <span class="brand-subtitle-temple d-block">A Divine Journey Together</span>
            </div>
        </a>

        <!-- Right Action Icons (Notification Bell & Hamburger Menu) -->
        <div class="d-flex align-items-center gap-2 order-lg-last">
            <!-- Notification Bell Icon -->
            <button type="button" class="header-icon-btn" data-bs-toggle="modal" data-bs-target="#notificationCenterModal" title="Alerts & Notifications">
                <i class="bi bi-bell fs-5"></i>
                <span class="notif-dot"></span>
            </button>

            <!-- Hamburger Menu Button -->
            <button class="header-icon-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenuOffcanvas" aria-controls="mobileMenuOffcanvas" aria-label="Toggle navigation">
                <i class="bi bi-list fs-3"></i>
            </button>
        </div>

        <!-- Desktop Navigation Links -->
        <div class="collapse navbar-collapse" id="desktopNavbar">
            <ul class="navbar-nav ms-auto align-items-center gap-3 my-2 my-lg-0">
                <li class="nav-item">
                    <a class="nav-link text-dark fw-bold <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'text-primary border-bottom border-2 border-danger' : '' ?>" href="<?= BASE_URL ?>/index.php">
                        <i class="bi bi-bus-front text-danger me-1"></i> Available Buses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-dark fw-bold <?= (basename($_SERVER['PHP_SELF']) == 'my-bookings.php') ? 'text-primary border-bottom border-2 border-danger' : '' ?>" href="<?= BASE_URL ?>/my-bookings.php">
                        <i class="bi bi-ticket-perforated text-danger me-1"></i> My Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-dark fw-semibold" href="#" data-bs-toggle="modal" data-bs-target="#templeInfoModal">
                        <i class="bi bi-bank2 text-warning me-1"></i> Temple Info
                    </a>
                </li>

                <?php if ($customer): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle btn btn-sm btn-outline-danger text-start px-3 py-1 rounded-pill" href="#" id="customerMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-check-fill me-1"></i> <?= htmlspecialchars($customer['name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="customerMenu">
                            <li><h6 class="dropdown-header"><?= htmlspecialchars($customer['phone']) ?></h6></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/my-bookings.php"><i class="bi bi-ticket-detailed me-2"></i>My Bookings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/my-bookings.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-semibold" href="<?= BASE_URL ?>/my-bookings.php">
                            <i class="bi bi-phone me-1"></i> Devotee OTP Login
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item ms-lg-2">
                    <a class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 shadow-sm" href="<?= BASE_URL ?>/admin/index.php">
                        <i class="bi bi-shield-lock-fill me-1"></i> Admin Portal
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Mobile Slide-out Offcanvas Navigation Drawer -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenuOffcanvas" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header border-bottom bg-light">
        <div class="d-flex align-items-center gap-2">
            <img src="<?= BASE_URL ?>/assets/images/temple_logo.png" alt="Temple" class="temple-logo-mark" onerror="this.outerHTML='<span class=\'fs-3 text-warning\'>🛕</span>'">
            <div>
                <h5 class="brand-title-temple mb-0" id="mobileMenuLabel">TempleYatra</h5>
                <span class="brand-subtitle-temple">A Divine Journey Together</span>
            </div>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4">
        <ul class="nav flex-column gap-2 mb-4">
            <li class="nav-item">
                <a class="nav-link text-dark fw-bold fs-5 py-2" href="<?= BASE_URL ?>/index.php">
                    <i class="bi bi-house-door-fill text-danger me-2"></i> Home
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark fw-bold fs-5 py-2" href="<?= BASE_URL ?>/index.php#available-buses">
                    <i class="bi bi-bus-front text-danger me-2"></i> Available Buses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark fw-bold fs-5 py-2" href="<?= BASE_URL ?>/my-bookings.php">
                    <i class="bi bi-ticket-perforated text-danger me-2"></i> My Bookings & Passes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark fw-bold fs-5 py-2" href="#" data-bs-toggle="modal" data-bs-target="#templeInfoModal" data-bs-dismiss="offcanvas">
                    <i class="bi bi-bank2 text-warning me-2"></i> Temple Darshan Info
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark fw-bold fs-5 py-2" href="#" data-bs-toggle="modal" data-bs-target="#aboutYatraModal" data-bs-dismiss="offcanvas">
                    <i class="bi bi-info-circle text-primary me-2"></i> About TempleYatra
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark fw-bold fs-5 py-2" href="<?= BASE_URL ?>/admin/index.php">
                    <i class="bi bi-shield-lock text-warning me-2"></i> Admin Portal
                </a>
            </li>
        </ul>

        <div class="card border-0 bg-warning bg-opacity-10 rounded-4 p-3 mb-4">
            <div class="small fw-bold text-dark mb-1"><i class="bi bi-telephone-fill text-danger me-1"></i> Need Pilgrimage Assistance?</div>
            <div class="text-muted small">Helpline: <?= htmlspecialchars($settings['destination_contact'] ?? '+91 94470 10808') ?></div>
            <div class="text-muted small mt-1">Destination: <?= htmlspecialchars($settings['destination_name'] ?? 'Temple Destination') ?></div>
        </div>

        <?php if ($customer): ?>
            <div class="d-grid">
                <a href="<?= BASE_URL ?>/my-bookings.php?logout=1" class="btn btn-outline-danger rounded-pill fw-bold">Sign Out</a>
            </div>
        <?php else: ?>
            <div class="d-grid">
                <a href="<?= BASE_URL ?>/my-bookings.php" class="btn btn-danger rounded-pill fw-bold">Devotee Login</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Live Notification Toast Container (Simulated SMS / WhatsApp Alert Popup) -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="notificationToastContainer" style="z-index: 1090;"></div>
