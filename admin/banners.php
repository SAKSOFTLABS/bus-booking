<?php
/**
 * Hero Section Slider Image Banners Management (Admin)
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create' || $action === 'update') {
        $title = cleanInput($_POST['title'] ?? '');
        $subtitle = cleanInput($_POST['subtitle'] ?? '');
        $imageUrl = cleanInput($_POST['image_url'] ?? '');
        $linkUrl = cleanInput($_POST['link_url'] ?? '#available-buses');
        $btnText = cleanInput($_POST['button_text'] ?? 'Book Yatra Bus Now');
        $order = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($imageUrl)) {
            setFlash('error', 'Banner Image URL is required.');
        } else {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO `hero_slides` 
                    (`title`, `subtitle`, `image_url`, `link_url`, `button_text`, `display_order`, `is_active`, `created_at`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$title, $subtitle, $imageUrl, $linkUrl, $btnText, $order, $isActive]);
                setFlash('success', 'New Hero Banner Slide created successfully!');
            } else {
                $slideId = (int)($_POST['slide_id'] ?? 0);
                $stmt = $db->prepare("
                    UPDATE `hero_slides` 
                    SET `title` = ?, `subtitle` = ?, `image_url` = ?, `link_url` = ?, 
                        `button_text` = ?, `display_order` = ?, `is_active` = ?
                    WHERE `id` = ?
                ");
                $stmt->execute([$title, $subtitle, $imageUrl, $linkUrl, $btnText, $order, $isActive, $slideId]);
                setFlash('success', 'Hero Banner Slide updated successfully!');
            }
            header('Location: ' . BASE_URL . '/admin/banners.php');
            exit;
        }
    } elseif ($action === 'toggle_status') {
        $slideId = (int)($_POST['slide_id'] ?? 0);
        $newStatus = (int)($_POST['status'] ?? 0);
        $stmt = $db->prepare("UPDATE `hero_slides` SET `is_active` = ? WHERE `id` = ?");
        $stmt->execute([$newStatus, $slideId]);
        setFlash('success', 'Slide status updated.');
        header('Location: ' . BASE_URL . '/admin/banners.php');
        exit;
    } elseif ($action === 'delete') {
        $slideId = (int)($_POST['slide_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM `hero_slides` WHERE `id` = ?");
        $stmt->execute([$slideId]);
        setFlash('success', 'Banner slide deleted.');
        header('Location: ' . BASE_URL . '/admin/banners.php');
        exit;
    }
}

// Fetch all slides ordered by display order
$slides = $db->query("SELECT * FROM `hero_slides` ORDER BY display_order ASC, id ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h3 class="fw-extrabold text-navy mb-1">Hero Slider Banner Management</h3>
        <p class="text-muted small mb-0">Control the customer homepage top carousel slider images, promotional titles, and action links.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/index.php" target="_blank" class="btn btn-outline-dark rounded-pill px-3 btn-sm">
            <i class="bi bi-eye me-1"></i> Preview Homepage
        </a>
        <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#slideModal">
            <i class="bi bi-plus-circle me-1"></i> Add New Slide
        </button>
    </div>
</div>

<!-- Banner Slides Cards List -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0"><i class="bi bi-images text-primary me-2"></i> Active Slider Banners (<?= count($slides) ?>)</h5>
    </div>

    <div class="card-body p-4">
        <?php if (empty($slides)): ?>
            <div class="text-center py-5">
                <i class="bi bi-image text-muted display-4 d-block mb-3"></i>
                <h5>No banner slides found</h5>
                <p class="text-muted small">Add your first promotional hero slider image banner.</p>
                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#slideModal">
                    Add Banner Slide
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order</th>
                            <th>Banner Image</th>
                            <th>Title & Subtitle</th>
                            <th>Action Link & Button</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slides as $s): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-dark rounded-pill"><?= $s['display_order'] ?></span>
                                </td>
                                <td>
                                    <div class="rounded-3 overflow-hidden border shadow-sm" style="width: 140px; height: 75px; background: #292524;">
                                        <img src="<?= htmlspecialchars($s['image_url']) ?>" alt="Slide" class="w-100 h-100 object-fit-cover">
                                    </div>
                                </td>
                                <td style="max-width: 280px;">
                                    <strong class="text-navy d-block mb-1"><?= htmlspecialchars($s['title'] ?: 'Untitled Banner') ?></strong>
                                    <span class="text-muted small d-block"><?= htmlspecialchars(substr($s['subtitle'] ?? '', 0, 85)) ?>...</span>
                                </td>
                                <td>
                                    <div class="small">
                                        <span class="text-muted d-block">Target Link:</span>
                                        <a href="<?= htmlspecialchars($s['link_url']) ?>" target="_blank" class="text-primary font-monospace text-decoration-none">
                                            <i class="bi bi-link-45deg"></i> <?= htmlspecialchars($s['link_url']) ?>
                                        </a>
                                        <div class="mt-1">
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-cursor-fill me-1 text-danger"></i> <?= htmlspecialchars($s['button_text'] ?: 'Clickable') ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($s['is_active']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success">Active Slide</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-dark border">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill me-1" onclick='openEditSlideModal(<?= json_encode($s) ?>)'>
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="slide_id" value="<?= $s['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $s['is_active'] ? 0 : 1 ?>">
                                        <?php if ($s['is_active']): ?>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill" title="Deactivate">
                                                <i class="bi bi-eye-slash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill" title="Activate">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this banner slide?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="slide_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ================= ADD / EDIT SLIDE MODAL ================= -->
<div class="modal fade" id="slideModal" tabindex="-1" aria-labelledby="slideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" id="slideForm">
                <input type="hidden" name="action" id="slideFormAction" value="create">
                <input type="hidden" name="slide_id" id="slideFormId" value="">

                <div class="modal-header bg-navy text-white border-0 py-3 px-4">
                    <h6 class="modal-title fw-bold" id="slideModalLabel">Add Hero Banner Slide</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Banner Image URL <span class="text-danger">*</span></label>
                        <input type="url" name="image_url" id="slideImgInput" class="form-control font-monospace" placeholder="https://images.unsplash.com/..." required oninput="previewSlideImage(this.value)">
                        <div class="form-text small">Recommended resolution: 1600x600 px or high-resolution landscape ratio.</div>
                        
                        <!-- Quick Presets -->
                        <div class="mt-2">
                            <span class="small text-muted me-2">Sample Presets:</span>
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill py-0 px-2 small" onclick="setPresetImg('https://images.unsplash.com/photo-1590050752117-238cb0fb12b1?auto=format&fit=crop&w=1600&q=80')">Temple Gopuram</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill py-0 px-2 small ms-1" onclick="setPresetImg('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=1600&q=80')">Volvo Yatra Bus</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill py-0 px-2 small ms-1" onclick="setPresetImg('https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=1600&q=80')">Deeparadhana Lamps</button>
                        </div>
                    </div>

                    <!-- Live Image Preview Box -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold d-block">Image Preview</label>
                        <div class="rounded-3 overflow-hidden border shadow-sm position-relative" style="height: 160px; background: #1c1917;">
                            <img id="liveSlidePreview" src="https://images.unsplash.com/photo-1590050752117-238cb0fb12b1?auto=format&fit=crop&w=1600&q=80" alt="Preview" class="w-100 h-100 object-fit-cover">
                            <div class="position-absolute bottom-0 start-0 end-0 p-2 text-white small" style="background: rgba(0,0,0,0.5);">
                                Preview display ratio
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Slide Headline Title</label>
                        <input type="text" name="title" id="slideTitleInput" class="form-control" placeholder="e.g. Divya Darshan Yatra to Sri Padmanabhaswamy Temple">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Slide Subtitle / Description</label>
                        <textarea name="subtitle" id="slideSubtitleInput" class="form-control" rows="2" placeholder="Brief devotional description for devotees"></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Target Action Link (URL) <span class="text-danger">*</span></label>
                            <input type="text" name="link_url" id="slideLinkInput" class="form-control font-monospace" value="#available-buses" required placeholder="e.g. #available-buses or bus-details.php?id=1">
                            <div class="form-text small">Where clicking the banner takes the user (e.g. <code>#available-buses</code> or <code>bus-details.php?id=1</code>).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Button CTA Text</label>
                            <input type="text" name="button_text" id="slideBtnTextInput" class="form-control" value="Book Yatra Bus Now" placeholder="e.g. Book Yatra Bus Now">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Display Sequence Order</label>
                            <input type="number" name="display_order" id="slideOrderInput" class="form-control" value="1" min="0">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="slideActiveInput" value="1" checked>
                                <label class="form-check-label small fw-bold" for="slideActiveInput">Slide is Active</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="slideSubmitBtn">Save Banner Slide</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setPresetImg(url) {
    document.getElementById('slideImgInput').value = url;
    previewSlideImage(url);
}

function previewSlideImage(url) {
    if (url) {
        document.getElementById('liveSlidePreview').src = url;
    }
}

function openEditSlideModal(slide) {
    document.getElementById('slideModalLabel').textContent = 'Edit Hero Banner Slide #' + slide.id;
    document.getElementById('slideFormAction').value = 'update';
    document.getElementById('slideFormId').value = slide.id;
    document.getElementById('slideImgInput').value = slide.image_url;
    document.getElementById('liveSlidePreview').src = slide.image_url;
    document.getElementById('slideTitleInput').value = slide.title || '';
    document.getElementById('slideSubtitleInput').value = slide.subtitle || '';
    document.getElementById('slideLinkInput').value = slide.link_url || '#available-buses';
    document.getElementById('slideBtnTextInput').value = slide.button_text || 'Book Yatra Bus Now';
    document.getElementById('slideOrderInput').value = slide.display_order;
    document.getElementById('slideActiveInput').checked = (parseInt(slide.is_active) === 1);
    document.getElementById('slideSubmitBtn').textContent = 'Update Slide';

    const modal = new bootstrap.Modal(document.getElementById('slideModal'));
    modal.show();
}

document.querySelector('[data-bs-target="#slideModal"]')?.addEventListener('click', function() {
    document.getElementById('slideModalLabel').textContent = 'Add Hero Banner Slide';
    document.getElementById('slideFormAction').value = 'create';
    document.getElementById('slideFormId').value = '';
    document.getElementById('slideForm').reset();
    document.getElementById('slideLinkInput').value = '#available-buses';
    document.getElementById('slideBtnTextInput').value = 'Book Yatra Bus Now';
    document.getElementById('slideSubmitBtn').textContent = 'Save Banner Slide';
    setPresetImg('https://images.unsplash.com/photo-1590050752117-238cb0fb12b1?auto=format&fit=crop&w=1600&q=80');
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
