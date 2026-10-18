<?php
/**
 * Predefined Fixed Destination & Hero Section Configuration (Admin Panel)
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_destination') {
    $name = cleanInput($_POST['destination_name'] ?? '');
    $landmark = cleanInput($_POST['destination_landmark'] ?? '');
    $address = cleanInput($_POST['destination_address'] ?? '');
    $contact = cleanInput($_POST['destination_contact'] ?? '');

    // Hero Section fields
    $heroTitle = cleanInput($_POST['hero_title'] ?? '');
    $heroSubtitle = cleanInput($_POST['hero_subtitle'] ?? '');
    $heroBadge = cleanInput($_POST['hero_badge_text'] ?? '');
    $heroBtnText = cleanInput($_POST['hero_btn_text'] ?? 'Book Yatra Bus Now');
    $heroBtnLink = cleanInput($_POST['hero_btn_link'] ?? '#available-buses');
    $heroImage = cleanInput($_POST['hero_image_url'] ?? '');
    $darshanInfo = cleanInput($_POST['temple_darshan_info'] ?? '');

    if (empty($name)) {
        setFlash('error', 'Destination temple name is required.');
    } else {
        $stmt = $db->prepare("
            UPDATE `settings` 
            SET `destination_name` = ?, `destination_landmark` = ?, `destination_address` = ?, `destination_contact` = ?,
                `hero_title` = ?, `hero_subtitle` = ?, `hero_badge_text` = ?, `hero_btn_text` = ?, `hero_btn_link` = ?,
                `hero_image_url` = ?, `temple_darshan_info` = ?
            WHERE id = 1
        ");
        $stmt->execute([
            $name, $landmark, $address, $contact,
            $heroTitle, $heroSubtitle, $heroBadge, $heroBtnText, $heroBtnLink,
            $heroImage, $darshanInfo
        ]);
        setFlash('success', 'Temple destination and Hero Section updated successfully!');
        header('Location: ' . BASE_URL . '/admin/destination.php');
        exit;
    }
}

$settings = getSystemSettings();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h3 class="fw-extrabold text-navy mb-1">Fixed Temple Destination & Hero Section</h3>
        <p class="text-muted small mb-0">Control the customer homepage hero banner, action button link, and sacred temple destination details.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <form method="POST">
            <input type="hidden" name="action" value="save_destination">

            <!-- Card 1: Homepage Hero Section (Requirement 1) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger text-white">Section 1</span>
                        <h5 class="fw-bold mb-0 text-navy">Homepage Hero Section Management</h5>
                    </div>
                    <p class="text-muted small mb-0">Manage the hero banner text, spiritual badge, action button link, and darshan notes.</p>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Hero Spiritual Badge Mantra</label>
                        <input type="text" name="hero_badge_text" class="form-control" value="<?= htmlspecialchars($settings['hero_badge_text'] ?? '') ?>" placeholder="e.g. ॐ Om Namo Bhagavate Vasudevaya • Daily Temple Special">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Hero Banner Title <span class="text-danger">*</span></label>
                        <input type="text" name="hero_title" class="form-control fw-bold" value="<?= htmlspecialchars($settings['hero_title'] ?? '') ?>" required placeholder="e.g. Divya Darshan Yatra to Sri Padmanabhaswamy Temple">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Hero Description / Subtitle</label>
                        <textarea name="hero_subtitle" class="form-control" rows="2" placeholder="Brief devotional description for devotees"><?= htmlspecialchars($settings['hero_subtitle'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Hero Button Text</label>
                            <input type="text" name="hero_btn_text" class="form-control" value="<?= htmlspecialchars($settings['hero_btn_text'] ?? 'Book Yatra Bus Now') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Hero Button Link / URL <span class="text-danger">*</span></label>
                            <input type="text" name="hero_btn_link" class="form-control font-monospace" value="<?= htmlspecialchars($settings['hero_btn_link'] ?? '#available-buses') ?>" placeholder="e.g. #available-buses or /bus/bus-details.php?id=1">
                            <div class="form-text small">Anchor link (e.g. <code>#available-buses</code>) or specific URL.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Hero Background Image URL</label>
                        <input type="url" name="hero_image_url" class="form-control font-monospace" value="<?= htmlspecialchars($settings['hero_image_url'] ?? '') ?>" placeholder="https://images.unsplash.com/...">
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold">Temple Darshan Timings & Guidelines</label>
                        <input type="text" name="temple_darshan_info" class="form-control" value="<?= htmlspecialchars($settings['temple_darshan_info'] ?? '') ?>" placeholder="e.g. Morning Darshan: 03:30 AM to 12:00 PM | Evening: 05:00 PM to 08:30 PM">
                    </div>
                </div>
            </div>

            <!-- Card 2: Predefined Fixed Temple Destination -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fw-bold">Fixed Destination</span>
                        <h5 class="fw-bold mb-0 text-navy">Sacred Temple Details</h5>
                    </div>
                    <p class="text-muted small mb-0">The single predefined service destination shown across passes and boarding stops.</p>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Temple Destination Name <span class="text-danger">*</span></label>
                        <input type="text" name="destination_name" class="form-control" value="<?= htmlspecialchars($settings['destination_name']) ?>" required placeholder="e.g. Sri Padmanabhaswamy Mahakshetram">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Arrival Landmark / Gopuram Entrance</label>
                        <input type="text" name="destination_landmark" class="form-control" value="<?= htmlspecialchars($settings['destination_landmark']) ?>" placeholder="e.g. East Nada (Kizhakkekotta) Main Gopuram Arrival Bay">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Temple Full Address</label>
                        <textarea name="destination_address" class="form-control" rows="2"><?= htmlspecialchars($settings['destination_address']) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Yatra Helpline Contact</label>
                        <input type="text" name="destination_contact" class="form-control" value="<?= htmlspecialchars($settings['destination_contact']) ?>" placeholder="e.g. +91 94470 10808">
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> Save All Settings
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Live Preview Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 sticky-top bg-white" style="top: 85px;">
            <div class="card-header bg-navy text-white p-4 rounded-top-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-eye me-1 text-warning"></i> Customer Live Preview</h5>
                <small class="text-white-50">How it looks on customer website</small>
            </div>
            <div class="card-body p-4">
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <span class="badge bg-warning text-dark mb-1 small"><?= htmlspecialchars($settings['hero_badge_text'] ?? 'ॐ Devotional Badge') ?></span>
                    <h5 class="fw-bold text-navy mt-1"><?= htmlspecialchars($settings['hero_title']) ?></h5>
                    <p class="small text-muted"><?= htmlspecialchars($settings['hero_subtitle']) ?></p>
                    <a href="#" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold disabled">
                        <?= htmlspecialchars($settings['hero_btn_text'] ?? 'Book Yatra Bus') ?> →
                    </a>
                </div>

                <div class="p-3 bg-danger bg-opacity-10 border border-danger rounded-3">
                    <div class="d-flex align-items-center gap-1 text-danger fw-bold small mb-1">
                        <span class="fs-5">ॐ</span> Fixed Temple Destination
                    </div>
                    <strong class="text-navy d-block"><?= htmlspecialchars($settings['destination_name']) ?></strong>
                    <div class="small text-muted mt-1"><?= htmlspecialchars($settings['destination_landmark']) ?></div>
                    <div class="small text-danger fw-bold mt-2"><i class="bi bi-telephone-fill me-1"></i> <?= htmlspecialchars($settings['destination_contact']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
