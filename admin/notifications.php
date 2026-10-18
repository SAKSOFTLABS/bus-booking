<?php
/**
 * Administrator SMS & WhatsApp Notification Logs & Simulation Monitor
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../../includes/notification.php';

$db = getDB();

// Handle Manual Test Alert Trigger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_test_alert') {
    $settings = getSystemSettings();
    $adminPhone = $settings['admin_alert_phone'] ?? '+919876543210';
    $msg = "🚨 [TEST 70% OCCUPANCY ALERT - " . APP_NAME . "]\n"
         . "Bus: Royal Express AC (KL-01-CB-4050)\n"
         . "Trip #TEST-1 has reached 72.5% occupancy!\n"
         . "Total Capacity: 40 seats | Booked: 29 seats | Remaining: 11 seats.\n"
         . "This is a simulated verification test of the 70% threshold alert system.";

    NotificationService::sendBoth($adminPhone, $msg, 'occupancy_70_alert', 'admin');
    setFlash('success', "Test 70% occupancy alert dispatched via SMS & WhatsApp to Admin ({$adminPhone})!");
    header('Location: ' . BASE_URL . '/admin/notifications.php');
    exit;
}

// Filters
$filterChannel = cleanInput($_GET['channel'] ?? '');
$filterType = cleanInput($_GET['recipient_type'] ?? '');
$filterEvent = cleanInput($_GET['event_type'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($filterChannel)) {
    $where[] = "channel = ?";
    $params[] = $filterChannel;
}
if (!empty($filterType)) {
    $where[] = "recipient_type = ?";
    $params[] = $filterType;
}
if (!empty($filterEvent)) {
    $where[] = "event_type = ?";
    $params[] = $filterEvent;
}

$whereSql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT * FROM `notification_logs` 
    WHERE {$whereSql} 
    ORDER BY id DESC LIMIT 100
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Statistics
$totalSent = $db->query("SELECT COUNT(*) FROM `notification_logs`")->fetchColumn() ?: 0;
$totalSMS = $db->query("SELECT COUNT(*) FROM `notification_logs` WHERE channel = 'sms'")->fetchColumn() ?: 0;
$totalWA = $db->query("SELECT COUNT(*) FROM `notification_logs` WHERE channel = 'whatsapp'")->fetchColumn() ?: 0;
$total70Alerts = $db->query("SELECT COUNT(*) FROM `notification_logs` WHERE event_type = 'occupancy_70_alert'")->fetchColumn() ?: 0;
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h3 class="fw-extrabold text-navy mb-1">SMS & WhatsApp Notifications Monitor</h3>
        <p class="text-muted small mb-0">Audit log of customer OTPs, booking confirmations, admin alerts, and 70% occupancy notifications.</p>
    </div>
    <form method="POST">
        <input type="hidden" name="action" value="send_test_alert">
        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
            <i class="bi bi-broadcast me-1"></i> Send Test 70% Occupancy Alert
        </button>
    </form>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-bold d-block">Total Dispatched</span>
                <h4 class="fw-extrabold mb-0"><?= number_format($totalSent) ?></h4>
            </div>
            <div class="kpi-icon bg-light text-primary"><i class="bi bi-send-fill"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-bold d-block">SMS Notifications</span>
                <h4 class="fw-extrabold text-primary mb-0"><?= number_format($totalSMS) ?></h4>
            </div>
            <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-chat-text-fill"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-bold d-block">WhatsApp Alerts</span>
                <h4 class="fw-extrabold text-success mb-0"><?= number_format($totalWA) ?></h4>
            </div>
            <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-whatsapp"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small fw-bold d-block">70% Occupancy Alerts</span>
                <h4 class="fw-extrabold text-danger mb-0"><?= number_format($total70Alerts) ?></h4>
            </div>
            <div class="kpi-icon bg-danger-subtle text-danger"><i class="bi bi-bell-fill"></i></div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="channel" class="form-select form-select-sm">
                    <option value="">-- All Channels (SMS & WhatsApp) --</option>
                    <option value="sms" <?= ($filterChannel === 'sms') ? 'selected' : '' ?>>SMS Only</option>
                    <option value="whatsapp" <?= ($filterChannel === 'whatsapp') ? 'selected' : '' ?>>WhatsApp Only</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="recipient_type" class="form-select form-select-sm">
                    <option value="">-- All Recipients --</option>
                    <option value="customer" <?= ($filterType === 'customer') ? 'selected' : '' ?>>Customer Messages</option>
                    <option value="admin" <?= ($filterType === 'admin') ? 'selected' : '' ?>>Admin Alerts</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="event_type" class="form-select form-select-sm">
                    <option value="">-- All Event Types --</option>
                    <option value="occupancy_70_alert" <?= ($filterEvent === 'occupancy_70_alert') ? 'selected' : '' ?>>70% Occupancy Alerts</option>
                    <option value="booking_confirmed" <?= ($filterEvent === 'booking_confirmed') ? 'selected' : '' ?>>Booking Confirmations</option>
                    <option value="otp_verification" <?= ($filterEvent === 'otp_verification') ? 'selected' : '' ?>>OTP Verifications</option>
                    <option value="admin_new_booking" <?= ($filterEvent === 'admin_new_booking') ? 'selected' : '' ?>>Admin New Bookings</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold">Filter Logs</button>
                <a href="<?= BASE_URL ?>/admin/notifications.php" class="btn btn-light btn-sm rounded-pill border">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Notification Audit Trail (Latest <?= count($logs) ?>)</h5>
        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="exportTableToCSV('notificationLogsTable', 'notification_logs.csv')">
            <i class="bi bi-download me-1"></i> Export CSV
        </button>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="notificationLogsTable">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>Channel</th>
                        <th>Target</th>
                        <th>Phone</th>
                        <th>Event Type</th>
                        <th>Message Content</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No notification logs match your filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): 
                            $isWA = ($l->channel ?? $l['channel']) === 'whatsapp';
                            $is70 = ($l['event_type'] === 'occupancy_70_alert');
                        ?>
                            <tr class="<?= $is70 ? 'table-warning bg-opacity-25' : '' ?>">
                                <td>
                                    <span><?= date('d M Y', strtotime($l['created_at'])) ?></span>
                                    <div class="text-muted" style="font-size: 0.72rem;"><?= date('h:i:s A', strtotime($l['created_at'])) ?></div>
                                </td>
                                <td>
                                    <?php if ($isWA): ?>
                                        <span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-whatsapp me-1"></i> WhatsApp</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary"><i class="bi bi-chat-text me-1"></i> SMS</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= ($l['recipient_type'] === 'admin') ? 'bg-dark' : 'bg-light text-dark border' ?> text-uppercase">
                                        <?= $l['recipient_type'] ?>
                                    </span>
                                </td>
                                <td><strong><?= htmlspecialchars($l['recipient_phone']) ?></strong></td>
                                <td>
                                    <?php if ($is70): ?>
                                        <span class="badge bg-danger"><i class="bi bi-bell-fill me-1"></i> 70% Occupancy Alert</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary text-uppercase"><?= str_replace('_', ' ', $l['event_type']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 380px;">
                                    <div class="text-dark font-monospace" style="white-space: pre-line; font-size: 0.78rem;">
                                        <?= htmlspecialchars($l['message']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success">
                                        <?= ucfirst($l['status']) ?>
                                    </span>
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
