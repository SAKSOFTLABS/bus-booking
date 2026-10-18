<?php
/**
 * Administrator System Settings
 * Gateway Credentials, Notification Providers, Thresholds, and Security
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'save_general') {
        $threshold = (float)($_POST['occupancy_threshold'] ?? 70.0);
        $adminPhone = cleanInput($_POST['admin_alert_phone'] ?? '');
        $adminEmail = cleanInput($_POST['admin_alert_email'] ?? '');
        $currencySymbol = cleanInput($_POST['currency_symbol'] ?? '₹');
        $currencyCode = cleanInput($_POST['currency_code'] ?? 'INR');

        $stmt = $db->prepare("
            UPDATE `settings` 
            SET `occupancy_threshold` = ?, `admin_alert_phone` = ?, `admin_alert_email` = ?, 
                `currency_symbol` = ?, `currency_code` = ?
            WHERE id = 1
        ");
        $stmt->execute([$threshold, $adminPhone, $adminEmail, $currencySymbol, $currencyCode]);
        setFlash('success', 'General settings and occupancy threshold updated successfully!');
        header('Location: ' . BASE_URL . '/admin/settings.php');
        exit;
    } elseif ($action === 'save_gateway') {
        $mode = cleanInput($_POST['payment_gateway_mode'] ?? 'sandbox');
        $keyId = cleanInput($_POST['razorpay_key_id'] ?? '');
        $keySecret = cleanInput($_POST['razorpay_key_secret'] ?? '');

        $stmt = $db->prepare("
            UPDATE `settings` 
            SET `payment_gateway_mode` = ?, `razorpay_key_id` = ?, `razorpay_key_secret` = ? 
            WHERE id = 1
        ");
        $stmt->execute([$mode, $keyId, $keySecret]);
        setFlash('success', 'Payment gateway configuration updated successfully!');
        header('Location: ' . BASE_URL . '/admin/settings.php');
        exit;
    } elseif ($action === 'save_notifications') {
        $mode = cleanInput($_POST['notification_mode'] ?? 'simulation');
        $smsProv = cleanInput($_POST['sms_provider'] ?? 'simulation');
        $smsKey = cleanInput($_POST['sms_api_key'] ?? '');
        $smsSender = cleanInput($_POST['sms_sender_id'] ?? 'BUSTRV');
        $waProv = cleanInput($_POST['whatsapp_provider'] ?? 'simulation');
        $waToken = cleanInput($_POST['whatsapp_api_token'] ?? '');
        $waPhoneId = cleanInput($_POST['whatsapp_phone_number_id'] ?? '');

        $stmt = $db->prepare("
            UPDATE `settings` 
            SET `notification_mode` = ?, `sms_provider` = ?, `sms_api_key` = ?, `sms_sender_id` = ?,
                `whatsapp_provider` = ?, `whatsapp_api_token` = ?, `whatsapp_phone_number_id` = ?
            WHERE id = 1
        ");
        $stmt->execute([$mode, $smsProv, $smsKey, $smsSender, $waProv, $waToken, $waPhoneId]);
        setFlash('success', 'SMS & WhatsApp API configurations saved successfully!');
        header('Location: ' . BASE_URL . '/admin/settings.php');
        exit;
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $adminId = $admin['id'];
        $stmt = $db->prepare("SELECT password_hash FROM `admins` WHERE id = ?");
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();

        if ($row && password_verify($currentPass, $row['password_hash'])) {
            if (strlen($newPass) >= 6 && $newPass === $confirmPass) {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                $db->prepare("UPDATE `admins` SET `password_hash` = ? WHERE id = ?")->execute([$hash, $adminId]);
                setFlash('success', 'Administrator password updated successfully!');
            } else {
                setFlash('error', 'New password must be at least 6 characters and match the confirmation.');
            }
        } else {
            setFlash('error', 'Current password was incorrect.');
        }
        header('Location: ' . BASE_URL . '/admin/settings.php');
        exit;
    }
}

$settings = getSystemSettings();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h3 class="fw-extrabold text-navy mb-1">System & Gateway Settings</h3>
        <p class="text-muted small mb-0">Configure online payment gateways, SMS/WhatsApp APIs, occupancy alert thresholds, and security.</p>
    </div>
</div>

<div class="row g-4">
    <!-- General Settings & Occupancy Threshold -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="bi bi-bell-fill text-danger me-2"></i> Occupancy Alert & Admin Contacts</h5>
                <p class="text-muted small mb-0">Configure alert threshold and admin recipient phone for 70% alerts.</p>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="save_general">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Seat Occupancy Alert Threshold (%)</label>
                        <div class="input-group">
                            <input type="number" step="1" name="occupancy_threshold" class="form-control" value="<?= htmlspecialchars($settings['occupancy_threshold']) ?>" min="10" max="100" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text small">Standard project rule is 70%. When total booked seats reach or cross this percentage, automated SMS/WhatsApp alerts will fire to the admin.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Admin Alert Mobile Number <span class="text-danger">*</span></label>
                        <input type="tel" name="admin_alert_phone" class="form-control" value="<?= htmlspecialchars($settings['admin_alert_phone']) ?>" required placeholder="+919876543210">
                        <div class="form-text small">Recipient phone for occupancy alerts and new booking notices.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Admin Alert Email</label>
                        <input type="email" name="admin_alert_email" class="form-control" value="<?= htmlspecialchars($settings['admin_alert_email']) ?>" placeholder="admin@busbooking.local">
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Currency Symbol</label>
                            <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($settings['currency_symbol']) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Currency Code</label>
                            <input type="text" name="currency_code" class="form-control" value="<?= htmlspecialchars($settings['currency_code']) ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Save General Settings</button>
                </form>
            </div>
        </div>

        <!-- Security / Password Change -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="bi bi-shield-lock-fill text-primary me-2"></i> Admin Security</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline-dark rounded-pill px-4 fw-bold">Update Password</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment Gateway & Notification Providers -->
    <div class="col-lg-6">
        <!-- Payment Gateway -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="bi bi-credit-card-2-front-fill text-success me-2"></i> Online Payment Gateway</h5>
                <p class="text-muted small mb-0">Switch between Sandbox Simulator and Live Razorpay integration.</p>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="save_gateway">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Gateway Mode</label>
                        <select name="payment_gateway_mode" class="form-select">
                            <option value="sandbox" <?= ($settings['payment_gateway_mode'] === 'sandbox') ? 'selected' : '' ?>>Interactive Sandbox Simulator (Recommended for testing)</option>
                            <option value="live" <?= ($settings['payment_gateway_mode'] === 'live') ? 'selected' : '' ?>>Live Razorpay Payment Gateway</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Razorpay Key ID</label>
                        <input type="text" name="razorpay_key_id" class="form-control font-monospace" value="<?= htmlspecialchars($settings['razorpay_key_id'] ?? '') ?>" placeholder="rzp_test_...">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Razorpay Key Secret</label>
                        <input type="password" name="razorpay_key_secret" class="form-control font-monospace" value="<?= htmlspecialchars($settings['razorpay_key_secret'] ?? '') ?>" placeholder="••••••••••••••">
                    </div>

                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Save Gateway Credentials</button>
                </form>
            </div>
        </div>

        <!-- SMS & WhatsApp Configuration -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="bi bi-chat-dots-fill text-info me-2"></i> SMS & WhatsApp Messaging Engine</h5>
                <p class="text-muted small mb-0">Simulation mode logs all messages safely to the database for testing without paid SMS charges.</p>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="save_notifications">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Notification Engine Mode</label>
                        <select name="notification_mode" class="form-select">
                            <option value="simulation" <?= ($settings['notification_mode'] === 'simulation') ? 'selected' : '' ?>>Simulation & Audit Logging (Free & Zero Setup)</option>
                            <option value="api" <?= ($settings['notification_mode'] === 'api') ? 'selected' : '' ?>>Live Cloud Provider APIs (Fast2SMS / Meta WhatsApp)</option>
                        </select>
                    </div>

                    <h6 class="fw-bold text-navy mt-4 mb-2">SMS Configuration</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">SMS Provider</label>
                            <select name="sms_provider" class="form-select form-select-sm">
                                <option value="simulation" <?= ($settings['sms_provider'] === 'simulation') ? 'selected' : '' ?>>Simulation Mode</option>
                                <option value="fast2sms" <?= ($settings['sms_provider'] === 'fast2sms') ? 'selected' : '' ?>>Fast2SMS</option>
                                <option value="twilio" <?= ($settings['sms_provider'] === 'twilio') ? 'selected' : '' ?>>Twilio SMS</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">SMS Sender ID</label>
                            <input type="text" name="sms_sender_id" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['sms_sender_id'] ?? 'BUSTRV') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">SMS API Key</label>
                        <input type="password" name="sms_api_key" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($settings['sms_api_key'] ?? '') ?>">
                    </div>

                    <h6 class="fw-bold text-navy mt-4 mb-2">WhatsApp Configuration</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">WhatsApp Provider</label>
                            <select name="whatsapp_provider" class="form-select form-select-sm">
                                <option value="simulation" <?= ($settings['whatsapp_provider'] === 'simulation') ? 'selected' : '' ?>>Simulation Mode</option>
                                <option value="meta_api" <?= ($settings['whatsapp_provider'] === 'meta_api') ? 'selected' : '' ?>>Meta Cloud API</option>
                                <option value="twilio_whatsapp" <?= ($settings['whatsapp_provider'] === 'twilio_whatsapp') ? 'selected' : '' ?>>Twilio WhatsApp</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Phone Number ID</label>
                            <input type="text" name="whatsapp_phone_number_id" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($settings['whatsapp_phone_number_id'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Meta Bearer Token</label>
                        <input type="password" name="whatsapp_api_token" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($settings['whatsapp_api_token'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-info text-white rounded-pill px-4 fw-bold">Save Messaging Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
