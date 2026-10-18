<?php
/**
 * Bus Management Module - Fleet, Images, Amenities & Types
 */

require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Handle Add / Edit Bus POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create' || $action === 'update') {
        $name = cleanInput($_POST['name'] ?? '');
        $regNo = strtoupper(cleanInput($_POST['registration_number'] ?? ''));
        $capacity = (int)($_POST['total_capacity'] ?? 0);
        $defaultRate = (float)($_POST['default_rate_per_seat'] ?? 0);
        $busType = cleanInput($_POST['bus_type'] ?? '2+2 Pushback AC Deluxe');
        $imageUrl = cleanInput($_POST['image_url'] ?? '');
        $amenities = cleanInput($_POST['amenities'] ?? 'AC, Pushback Seats, Sacred Chants, Water Bottle');
        $rating = (float)($_POST['rating'] ?? 4.8);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || empty($regNo) || $capacity < 1) {
            setFlash('error', 'Please fill in all required fields (Name, Registration No, and Capacity >= 1).');
        } else {
            if ($action === 'create') {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO `buses` 
                        (`name`, `registration_number`, `total_capacity`, `default_rate_per_seat`, `bus_type`, `image_url`, `amenities`, `rating`, `is_active`, `created_at`) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$name, $regNo, $capacity, $defaultRate, $busType, $imageUrl, $amenities, $rating, $isActive]);
                    setFlash('success', 'New Yatra bus added successfully!');
                } catch (PDOException $e) {
                    setFlash('error', 'Error adding bus. Registration number might already exist.');
                }
            } else {
                $busId = (int)($_POST['bus_id'] ?? 0);
                try {
                    $stmt = $db->prepare("
                        UPDATE `buses` 
                        SET `name` = ?, `registration_number` = ?, `total_capacity` = ?, `default_rate_per_seat` = ?, 
                            `bus_type` = ?, `image_url` = ?, `amenities` = ?, `rating` = ?, `is_active` = ? 
                        WHERE `id` = ?
                    ");
                    $stmt->execute([$name, $regNo, $capacity, $defaultRate, $busType, $imageUrl, $amenities, $rating, $isActive, $busId]);
                    setFlash('success', 'Yatra bus updated successfully!');
                } catch (PDOException $e) {
                    setFlash('error', 'Error updating bus: ' . $e->getMessage());
                }
            }
            header('Location: ' . BASE_URL . '/admin/buses.php');
            exit;
        }
    } elseif ($action === 'toggle_status') {
        $busId = (int)($_POST['bus_id'] ?? 0);
        $newStatus = (int)($_POST['status'] ?? 0);
        $stmt = $db->prepare("UPDATE `buses` SET `is_active` = ? WHERE `id` = ?");
        $stmt->execute([$newStatus, $busId]);
        setFlash('success', 'Bus status updated.');
        header('Location: ' . BASE_URL . '/admin/buses.php');
        exit;
    }
}

// Fetch all buses with aggregated trip statistics
$buses = $db->query("
    SELECT b.*,
           COUNT(t.id) as total_trips,
           COALESCE(SUM(t.booked_seats), 0) as total_seats_booked,
           COALESCE(SUM(t.total_capacity), 0) as cumulative_capacity
    FROM `buses` b
    LEFT JOIN `trips` t ON t.bus_id = b.id
    GROUP BY b.id
    ORDER BY b.id DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h3 class="fw-extrabold text-navy mb-1">Yatra Bus Fleet Management</h3>
        <p class="text-muted small mb-0">Configure buses, poster images, bus types, amenities, seat capacities, and default rates.</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#busModal">
        <i class="bi bi-plus-circle me-1"></i> Add New Bus
    </button>
</div>

<!-- Buses List Table Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0"><i class="bi bi-bus-front text-primary me-2"></i> Registered Fleet (<?= count($buses) ?>)</h5>
        <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="exportTableToCSV('busTable', 'bus_fleet.csv')">
            <i class="bi bi-download me-1"></i> Export CSV
        </button>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="busTable">
                <thead class="table-light">
                    <tr>
                        <th>Poster</th>
                        <th>Bus Name</th>
                        <th>Type & Rating</th>
                        <th>Reg Number</th>
                        <th>Capacity</th>
                        <th>Default Rate</th>
                        <th>Bookings</th>
                        <th>Occupancy</th>
                        <th>Status</th>
                        <th class="text-end no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($buses)): ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">No buses added yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($buses as $bus): 
                            $overallOcc = calculateOccupancy($bus['total_seats_booked'], $bus['cumulative_capacity']);
                            $bImg = !empty($bus['image_url']) ? $bus['image_url'] : 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=400&q=80';
                        ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($bImg) ?>" alt="<?= htmlspecialchars($bus['name']) ?>" style="width: 55px; height: 38px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                                </td>
                                <td>
                                    <strong class="text-navy d-block"><?= htmlspecialchars($bus['name']) ?></strong>
                                    <div class="text-muted small" style="font-size: 0.72rem;"><?= htmlspecialchars(substr($bus['amenities'] ?? '', 0, 40)) ?>...</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($bus['bus_type'] ?? '2+2 AC') ?></span>
                                    <span class="badge bg-warning text-dark small ms-1">★ <?= $bus['rating'] ?? 4.8 ?></span>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-dark border font-monospace"><?= htmlspecialchars($bus['registration_number']) ?></span></td>
                                <td><strong><?= $bus['total_capacity'] ?></strong> Seats</td>
                                <td><?= formatCurrency($bus['default_rate_per_seat']) ?></td>
                                <td>
                                    <span><?= $bus['total_seats_booked'] ?> seats</span>
                                    <div class="text-muted small"><?= $bus['total_trips'] ?> trip(s)</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="occupancy-progress flex-grow-1" style="width: 70px;">
                                            <div class="progress-bar <?= ($overallOcc >= 70) ? 'bg-danger' : 'bg-primary' ?>" style="width: <?= min(100, $overallOcc) ?>%"></div>
                                        </div>
                                        <span class="small fw-bold <?= ($overallOcc >= 70) ? 'text-danger' : 'text-dark' ?>"><?= $overallOcc ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($bus['is_active']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger">Deactivated</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end no-export">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill me-1" 
                                            onclick='openEditBusModal(<?= json_encode($bus) ?>)'>
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="bus_id" value="<?= $bus['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $bus['is_active'] ? 0 : 1 ?>">
                                        <?php if ($bus['is_active']): ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" title="Deactivate Bus">
                                                <i class="bi bi-power"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill" title="Activate Bus">
                                                <i class="bi bi-check2"></i>
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

<!-- ================= BUS ADD / EDIT MODAL ================= -->
<div class="modal fade" id="busModal" tabindex="-1" aria-labelledby="busModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form method="POST" id="busForm">
                <input type="hidden" name="action" id="busFormAction" value="create">
                <input type="hidden" name="bus_id" id="busFormId" value="">

                <div class="modal-header bg-navy text-white border-0 py-3 px-4">
                    <h6 class="modal-title fw-bold" id="busModalLabel">Add New Bus</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Bus Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="busNameInput" class="form-control" placeholder="e.g. Garuda Yatra AC Volvo Express" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Registration Number <span class="text-danger">*</span></label>
                            <input type="text" name="registration_number" id="busRegInput" class="form-control font-monospace" placeholder="e.g. KL-01-CB-4050" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Bus Type / Class</label>
                            <input type="text" name="bus_type" id="busTypeInput" class="form-control" placeholder="e.g. Multi-Axle AC Airavat, 2+2 Pushback Deluxe">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Rating (★)</label>
                            <input type="number" step="0.1" name="rating" id="busRatingInput" class="form-control" value="4.8" min="1" max="5">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Total Capacity <span class="text-danger">*</span></label>
                            <input type="number" name="total_capacity" id="busCapInput" class="form-control" value="40" min="1" max="100" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Bus Image URL (For Poster Card)</label>
                            <input type="url" name="image_url" id="busImgInput" class="form-control font-monospace" placeholder="https://images.unsplash.com/...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Default Rate / Seat (<?= CURRENCY_SYMBOL ?>)</label>
                            <input type="number" step="0.5" name="default_rate_per_seat" id="busRateInput" class="form-control" value="250.00" min="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Amenities & Features (Comma separated)</label>
                        <input type="text" name="amenities" id="busAmenitiesInput" class="form-control" placeholder="e.g. AC, Pushback Seats, Sacred Bhajan Audio, Mineral Water">
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="busActiveInput" value="1" checked>
                        <label class="form-check-label small fw-bold" for="busActiveInput">Bus is Active for Scheduling</label>
                    </div>
                </div>

                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="busSubmitBtn">Save Bus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditBusModal(bus) {
    document.getElementById('busModalLabel').textContent = 'Edit Bus: ' + bus.name;
    document.getElementById('busFormAction').value = 'update';
    document.getElementById('busFormId').value = bus.id;
    document.getElementById('busNameInput').value = bus.name;
    document.getElementById('busRegInput').value = bus.registration_number;
    document.getElementById('busCapInput').value = bus.total_capacity;
    document.getElementById('busRateInput').value = bus.default_rate_per_seat;
    document.getElementById('busTypeInput').value = bus.bus_type || '2+2 Pushback AC Deluxe';
    document.getElementById('busRatingInput').value = bus.rating || 4.8;
    document.getElementById('busImgInput').value = bus.image_url || '';
    document.getElementById('busAmenitiesInput').value = bus.amenities || '';
    document.getElementById('busActiveInput').checked = (parseInt(bus.is_active) === 1);
    document.getElementById('busSubmitBtn').textContent = 'Update Bus';

    const modal = new bootstrap.Modal(document.getElementById('busModal'));
    modal.show();
}

// Reset modal on add click
document.querySelector('[data-bs-target="#busModal"]')?.addEventListener('click', function() {
    document.getElementById('busModalLabel').textContent = 'Add New Bus';
    document.getElementById('busFormAction').value = 'create';
    document.getElementById('busFormId').value = '';
    document.getElementById('busForm').reset();
    document.getElementById('busSubmitBtn').textContent = 'Save Bus';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
