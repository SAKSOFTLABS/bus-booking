<?php
/**
 * Admin Portal Header & Sidebar Layout
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

AuthService::requireAdminAuth();

$admin = AuthService::getAdmin();
$settings = getSystemSettings();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - <?= APP_NAME ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const CURRENCY_SYMBOL = '<?= CURRENCY_SYMBOL ?>';
    </script>
</head>
<body class="admin-body">

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-brand d-flex align-items-center gap-2">
            <div class="brand-icon" style="width: 36px; height: 36px; font-size: 1.1rem;">
                <i class="bi bi-bus-front-fill"></i>
            </div>
            <div>
                <strong class="text-white d-block" style="font-size: 1.05rem;"><?= APP_NAME ?></strong>
                <span class="badge bg-primary text-uppercase" style="font-size: 0.65rem;">Administrator</span>
            </div>
        </div>

        <div class="py-3 d-flex flex-column gap-1 flex-grow-1 overflow-auto">
            <span class="px-3 text-uppercase text-white-50" style="font-size: 0.68rem; letter-spacing: 1px; font-weight: 700;">Core Management</span>
            <a href="<?= BASE_URL ?>/admin/index.php" class="admin-nav-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/buses.php" class="admin-nav-item <?= ($currentPage == 'buses.php') ? 'active' : '' ?>">
                <i class="bi bi-bus-front"></i> Bus Management
            </a>
            <a href="<?= BASE_URL ?>/admin/trips.php" class="admin-nav-item <?= ($currentPage == 'trips.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-check"></i> Trips & Occupancy
            </a>
            <a href="<?= BASE_URL ?>/admin/pickups.php" class="admin-nav-item <?= ($currentPage == 'pickups.php') ? 'active' : '' ?>">
                <i class="bi bi-geo-alt"></i> Pickup Points
            </a>
            <a href="<?= BASE_URL ?>/admin/bookings.php" class="admin-nav-item <?= ($currentPage == 'bookings.php') ? 'active' : '' ?>">
                <i class="bi bi-ticket-perforated"></i> Bookings & Sales
            </a>

            <span class="px-3 text-uppercase text-white-50 mt-3" style="font-size: 0.68rem; letter-spacing: 1px; font-weight: 700;">Reports & Logs</span>
            <a href="<?= BASE_URL ?>/admin/reports.php" class="admin-nav-item <?= ($currentPage == 'reports.php') ? 'active' : '' ?>">
                <i class="bi bi-graph-up-arrow"></i> Reports & Analytics
            </a>
            <a href="<?= BASE_URL ?>/admin/notifications.php" class="admin-nav-item <?= ($currentPage == 'notifications.php') ? 'active' : '' ?>">
                <i class="bi bi-bell"></i> SMS & WhatsApp Logs
            </a>

            <span class="px-3 text-uppercase text-white-50 mt-3" style="font-size: 0.68rem; letter-spacing: 1px; font-weight: 700;">System Settings</span>
            <a href="<?= BASE_URL ?>/admin/banners.php" class="admin-nav-item <?= ($currentPage == 'banners.php') ? 'active' : '' ?>">
                <i class="bi bi-images"></i> Hero Slider Banners
            </a>
            <a href="<?= BASE_URL ?>/admin/destination.php" class="admin-nav-item <?= ($currentPage == 'destination.php') ? 'active' : '' ?>">
                <i class="bi bi-pin-map"></i> Fixed Destination
            </a>
            <a href="<?= BASE_URL ?>/admin/settings.php" class="admin-nav-item <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">
                <i class="bi bi-sliders"></i> Gateway & Alerts
            </a>
        </div>

        <div class="p-3 border-top border-secondary border-opacity-25 mt-auto">
            <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-sm btn-outline-light w-100 mb-2">
                <i class="bi bi-box-arrow-up-right me-1"></i> View Customer Site
            </a>
            <a href="<?= BASE_URL ?>/admin/logout.php" class="btn btn-sm btn-danger w-100">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Container -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none border" id="toggleSidebarBtn">
                    <i class="bi bi-list"></i>
                </button>
                <div class="d-none d-md-block">
                    <span class="text-muted small"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Predefined Destination:</span>
                    <strong class="text-dark small ms-1"><?= htmlspecialchars($settings['destination_name']) ?></strong>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <strong class="d-block small"><?= htmlspecialchars($admin['name']) ?></strong>
                    <span class="text-muted" style="font-size: 0.75rem;">@<?= htmlspecialchars($admin['username']) ?></span>
                </div>
                <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">
                    <?= strtoupper(substr($admin['username'], 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- Main Content Body -->
        <main class="admin-content">
            <?php if (hasFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars(getFlash('success')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (hasFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars(getFlash('error')) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
