<?php
/**
 * Pickup Point Management Module
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle Add / Edit Pickup Point POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create' || $action === 'update') {
        $name = cleanInput($_POST['name'] ?? '');
        $landmark = cleanInput($_POST['landmark'] ?? '');
        $address = cleanInput($_POST['address'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name)) {
            setFlash('error', 'Pickup point name is required.');
        } else {
            if ($action === 'create') {
                $stmt = $db->prepare("
                    INSERT INTO `pickup_points` (`name`, `landmark`, `address`, `display_order`, `is_active`, `created_at`)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $landmark, $address, $displayOrder, $isActive]);
                setFlash('success', 'Pickup point added successfully!');
            } else {
                $pickupId = (int)($_POST['pickup_id'] ?? 0);
                $stmt = $db->prepare("
                    UPDATE `pickup_points` 
                    SET `name` = ?, `landmark` = ?, `address` = ?, `display_order` = ?, `is_active` = ?
                    WHERE `id` = ?
                ");
                $stmt->execute([$name, $landmark, $address, $displayOrder, $isActive, $pickupId]);
                setFlash('success', 'Pickup point updated successfully!');
            }
            header('Location: ' . BASE_URL . '/admin/pickups.php');
            exit;
        }
    } elseif ($action === 'toggle_status') {
        $pickupId = (int)($_POST['pickup_id'] ?? 0);
        $newStatus = (int)($_POST['status'] ?? 0);
        $stmt = $db->prepare("UPDATE `pickup_points` SET `is_active` = ? WHERE `id` = ?");
        $stmt->execute([$newStatus, $pickupId]);
        setFlash('success', 'Pickup point status updated.');
        header('Location: ' . BASE_URL . '/admin/pickups.php');
        exit;
    }
}

// Fetch all pickup points with booking counts
$pickupPoints = $db->query("
    SELECT p.*,
           (SELECT COUNT(*) FROM `bookings` b WHERE b.pickup_point_id = p.id AND b.booking_status = 'confirmed') as total_boardings
    FROM `pickup_points` p
    ORDER BY p.display_order ASC, p.id ASC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h3 class="fw-extrabold text-navy mb-1">Pickup Points Management</h3>
        <p class="text-muted small mb-0">Define boarding stops along the direct route to the predefined destination.</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#pickupModal">
        <i class="bi bi-geo-alt-fill me-1"></i> Add Pickup Point
    </button>
</div>

<!-- Pickups List Table Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0"><i class="bi bi-signpost-split text-primary me-2"></i> Route Boarding Stops (<?= count($pickupPoints) ?>)</h5>
        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="exportTableToCSV('pickupTable', 'pickup_points.csv')">
            <i class="bi bi-download me-1"></i> Export CSV
        </button>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="pickupTable">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>Pickup Point Name</th>
                        <th>Landmark</th>
                        <th>Address / Notes</th>
                        <th>Total Boardings</th>
                        <th>Status</th>
                        <th class="text-end no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pickupPoints)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No pickup points added yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pickupPoints as $p): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><?= $p['display_order'] ?></span></td>
                                <td>
                                    <strong class="text-navy"><?= htmlspecialchars($p['name']) ?></strong>
                                </td>
                                <td>
                                    <span class="text-muted small"><i class="bi bi-pin-map me-1 text-danger"></i> <?= htmlspecialchars($p['landmark'] ?? 'N/A') ?></span>
                                </td>
                                <td>
                                    <span class="small text-muted"><?= htmlspecialchars($p['address'] ?? '-') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary px-3"><?= $p['total_boardings'] ?> passengers</span>
                                </td>
                                <td>
                                    <?php if ($p['is_active']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end no-export">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill me-1" 
                                            onclick='openEditPickupModal(<?= json_encode($p) ?>)'>
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="pickup_id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $p['is_active'] ? 0 : 1 ?>">
                                        <?php if ($p['is_active']): ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" title="Deactivate">
                                                <i class="bi bi-power"></i> Deactivate
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill" title="Activate">
                                                <i class="bi bi-check2"></i> Activate
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= PICKUP ADD / EDIT MODAL ================= -->
<div class="modal fade" id="pickupModal" tabindex="-1" aria-labelledby="pickupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" id="pickupForm">
                <input type="hidden" name="action" id="pickupFormAction" value="create">
                <input type="hidden" name="pickup_id" id="pickupFormId" value="">

                <div class="modal-header bg-navy text-white border-0 py-3 px-4">
                    <h6 class="modal-title fw-bold" id="pickupModalLabel">Add Pickup Point</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pickup Point Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="pickupNameInput" class="form-control" placeholder="e.g. Thampanoor Central Stand" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Landmark / Prominent Spot</label>
                        <input type="text" name="landmark" id="pickupLandmarkInput" class="form-control" placeholder="e.g. Opposite Railway Station Exit">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Address / Route Description</label>
                        <textarea name="address" id="pickupAddressInput" class="form-control" rows="2" placeholder="e.g. Near platform 1 bus bay"></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Display Route Order</label>
                            <input type="number" name="display_order" id="pickupOrderInput" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="pickupActiveInput" value="1" checked>
                                <label class="form-check-label small fw-bold" for="pickupActiveInput">Active Stop</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="pickupSubmitBtn">Save Pickup Point</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditPickupModal(p) {
    document.getElementById('pickupModalLabel').textContent = 'Edit Pickup: ' + p.name;
    document.getElementById('pickupFormAction').value = 'update';
    document.getElementById('pickupFormId').value = p.id;
    document.getElementById('pickupNameInput').value = p.name;
    document.getElementById('pickupLandmarkInput').value = p.landmark || '';
    document.getElementById('pickupAddressInput').value = p.address || '';
    document.getElementById('pickupOrderInput').value = p.display_order;
    document.getElementById('pickupActiveInput').checked = (parseInt(p.is_active) === 1);
    document.getElementById('pickupSubmitBtn').textContent = 'Update Pickup Point';

    const modal = new bootstrap.Modal(document.getElementById('pickupModal'));
    modal.show();
}

document.querySelector('[data-bs-target="#pickupModal"]')?.addEventListener('click', function() {
    document.getElementById('pickupModalLabel').textContent = 'Add Pickup Point';
    document.getElementById('pickupFormAction').value = 'create';
    document.getElementById('pickupFormId').value = '';
    document.getElementById('pickupForm').reset();
    document.getElementById('pickupSubmitBtn').textContent = 'Save Pickup Point';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
