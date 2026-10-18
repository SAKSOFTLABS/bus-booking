<?php
/**
 * Customer Public Footer - Hindu Devotional Theme
 */
?>
<footer class="bg-maroon text-light pt-5 pb-4 mt-auto border-top border-4 border-warning">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="brand-icon">
                        <i class="bi bi-flower1"></i>
                    </div>
                    <div>
                        <span class="fs-4 fw-bold text-white"><?= APP_NAME ?></span>
                        <div class="text-warning small">ॐ सर्वे भवन्तु सुखिनः सर्वे सन्तु निरामयाः</div>
                    </div>
                </div>
                <p class="text-white-50 small">
                    Dedicated temple pilgrimage express bus services. Travel with divine peace, sacred bhajan chanting, comfortable seating, and punctual arrival for holy Temple Darshan.
                </p>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-warning text-dark fw-bold">Daily Temple Special</span>
                    <span class="badge bg-danger border border-warning text-white">Direct Yatra Route</span>
                    <span class="badge bg-light text-dark">Instant Devotee Pass</span>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-warning fw-bold mb-3"><i class="bi bi-compass me-1"></i> Quick Navigation</h6>
                <ul class="list-unstyled text-white-50 small mb-0 d-flex flex-column gap-2">
                    <li><a href="<?= BASE_URL ?>/index.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right text-warning me-1"></i> All Yatra Buses</a></li>
                    <li><a href="<?= BASE_URL ?>/my-bookings.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right text-warning me-1"></i> Devotee OTP Login</a></li>
                    <li><a href="<?= BASE_URL ?>/my-bookings.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right text-warning me-1"></i> My Yatra Passes & Tickets</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/login.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-chevron-right text-warning me-1"></i> Admin Portal</a></li>
                </ul>
            </div>

            <div class="col-lg-5 col-md-12">
                <h6 class="text-warning fw-bold mb-3"><i class="bi bi-geo-alt-fill me-1"></i> Sacred Temple Destination</h6>
                <div class="card bg-dark bg-opacity-50 border-warning p-3 text-white small">
                    <div class="d-flex align-items-start gap-2">
                        <span class="text-warning fs-4">ॐ</span>
                        <div>
                            <strong class="text-warning fs-6"><?= htmlspecialchars($settings['destination_name'] ?? 'Sacred Temple Destination') ?></strong>
                            <div class="mt-1 text-white-50"><i class="bi bi-pin-map text-warning me-1"></i> <?= htmlspecialchars($settings['destination_landmark'] ?? '') ?></div>
                            <div class="mt-1 text-white-50"><?= htmlspecialchars($settings['destination_address'] ?? '') ?></div>
                            <div class="mt-2 text-warning fw-bold"><i class="bi bi-telephone-fill me-1"></i> Helpline: <?= htmlspecialchars($settings['destination_contact'] ?? '') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="border-warning opacity-25 my-4">

        <div class="row align-items-center small text-white-50">
            <div class="col-md-6 text-center text-md-start">
                &copy; <?= date('Y') ?> TempleYatra. Holy Pilgrimage Transport Services.
            </div>
            <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                <span class="text-warning">Har Har Mahadev • Swamiye Saranam Ayyappa • Om Namo Narayanaya</span>
            </div>
        </div>
    </div>
</footer>

<!-- ================= MOBILE BOTTOM NAVIGATION BAR (MOCKUP ACCURACY) ================= -->
<div class="mobile-bottom-nav d-md-none">
    <a href="<?= BASE_URL ?>/index.php" class="mobile-nav-item <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
        <i class="bi bi-house-door-fill"></i>
        <span>Home</span>
    </a>
    <a href="<?= BASE_URL ?>/my-bookings.php" class="mobile-nav-item <?= (basename($_SERVER['PHP_SELF']) == 'my-bookings.php') ? 'active' : '' ?>">
        <i class="bi bi-search"></i>
        <span>My Bookings</span>
    </a>
    <button type="button" class="mobile-nav-item" data-bs-toggle="modal" data-bs-target="#templeInfoModal">
        <i class="bi bi-bank2"></i>
        <span>Temple Info</span>
    </button>
    <button type="button" class="mobile-nav-item" data-bs-toggle="modal" data-bs-target="#aboutYatraModal">
        <i class="bi bi-file-earmark-text"></i>
        <span>About</span>
    </button>
    <a href="<?= BASE_URL ?>/my-bookings.php" class="mobile-nav-item">
        <i class="bi bi-person"></i>
        <span>Profile</span>
    </a>
</div>

<!-- Temple Darshan Information Modal -->
<div class="modal fade" id="templeInfoModal" tabindex="-1" aria-labelledby="templeInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom bg-warning bg-opacity-10">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-4 text-warning">🛕</span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="templeInfoModalLabel">Temple Darshan Details</h5>
                        <small class="text-muted">Auspicious Pilgrimage Information</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small text-muted text-uppercase fw-bold">Fixed Sacred Destination</label>
                    <h5 class="fw-bold text-navy mb-1"><?= htmlspecialchars($settings['destination_name'] ?? 'Sri Padmanabhaswamy Mahakshetram') ?></h5>
                    <p class="text-muted small mb-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($settings['destination_landmark'] ?? 'East Nada Main Gopuram Arrival Bay') ?></p>
                </div>
                <hr class="my-3">
                <div class="mb-3">
                    <label class="small text-muted text-uppercase fw-bold mb-1">Holy Darshan Timings</label>
                    <div class="bg-light p-3 rounded-3 small">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-sun text-warning fs-5"></i>
                            <div><strong>Morning Pooja:</strong> 03:30 AM to 12:00 PM (Usha Pooja, Nirmalya Darshan)</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-moon-stars text-primary fs-5"></i>
                            <div><strong>Evening Pooja:</strong> 05:00 PM to 08:30 PM (Deeparadhana & Athazha Pooja)</div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted text-uppercase fw-bold mb-1">Pilgrimage Helpline</label>
                    <div class="d-flex align-items-center gap-2 text-danger fw-bold">
                        <i class="bi bi-telephone-fill"></i>
                        <span><?= htmlspecialchars($settings['destination_contact'] ?? '+91 94470 10808') ?></span>
                    </div>
                </div>
                <div class="alert alert-warning mb-0 small">
                    <i class="bi bi-info-circle-fill me-1"></i> Please follow traditional temple dress code (Dhoti/Mundu for men, Sarees/Salwar for women).
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold text-dark w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- About Yatra Modal -->
<div class="modal fade" id="aboutYatraModal" tabindex="-1" aria-labelledby="aboutYatraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark mb-0" id="aboutYatraModalLabel">About TempleYatra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-muted small">
                <p><strong>TempleYatra</strong> is dedicated to organizing convenient, safe, and spiritually enriching bus transit directly from your boarding stops to the sacred temple.</p>
                <div class="d-flex flex-column gap-2 my-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i> Direct travel to temple gates without transit changes
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i> Punctual departures aligned with auspicious Darshan timings
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i> Comfortable AC & Pushback Deluxe seating
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-success"></i> Instant mobile OTP booking & WhatsApp Yatra pass
                    </div>
                </div>
                <p class="mb-0">Designed for families, senior citizens, and pilgrimage groups looking for a peaceful journey.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-dark rounded-pill px-4 fw-bold w-100" data-bs-dismiss="modal">Understood</button>
            </div>
        </div>
    </div>
</div>

<!-- Notification Center Modal -->
<div class="modal fade" id="notificationCenterModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-bell-fill text-danger fs-5"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="notificationModalLabel">Notifications & Alerts</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex flex-column gap-3">
                    <div class="p-3 rounded-3 bg-light border-start border-4 border-warning">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <strong>Darshan Special Notice</strong>
                            <span>Today</span>
                        </div>
                        <p class="small text-dark mb-0">Daily Morning Temple Darshan bus services running on scheduled time. Book early to secure seats.</p>
                    </div>
                    <div class="p-3 rounded-3 bg-light border-start border-4 border-success">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <strong>Instant WhatsApp Confirmation</strong>
                            <span>Active</span>
                        </div>
                        <p class="small text-dark mb-0">Devotees receive real-time booking confirmation and digital boarding passes via SMS and WhatsApp.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main App JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
