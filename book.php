<?php
/**
 * Customer Seat Selection & Devotee Names Booking Page
 * BookMyShow Workflow: Number of Seats -> Names of Travellers -> Mobile Number -> OTP -> Payment
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$tripId = (int)($_GET['trip_id'] ?? 0);
if (!$tripId) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$db = getDB();
$settings = getSystemSettings();
$customer = AuthService::getCustomer();

// Fetch Trip & Bus Details
$stmt = $db->prepare("
    SELECT t.*, b.name as bus_name, b.registration_number, b.bus_type, b.image_url, b.rating, b.amenities
    FROM `trips` t
    JOIN `buses` b ON t.bus_id = b.id
    WHERE t.id = ? AND t.status = 'scheduled' AND b.is_active = 1
");
$stmt->execute([$tripId]);
$trip = $stmt->fetch();

if (!$trip) {
    die("Selected trip is not available or has been cancelled.");
}

$remainingSeats = max(0, $trip['total_capacity'] - $trip['booked_seats']);
if ($remainingSeats <= 0) {
    die("Sorry, this trip is completely full.");
}

// Fetch available pickup points for this trip
$pickupStmt = $db->prepare("
    SELECT p.*, tpp.pickup_time
    FROM `pickup_points` p
    LEFT JOIN `trip_pickup_points` tpp ON tpp.pickup_point_id = p.id AND tpp.trip_id = ?
    WHERE p.is_active = 1
    ORDER BY p.display_order ASC
");
$pickupStmt->execute([$tripId]);
$pickupPoints = $pickupStmt->fetchAll();

$busImg = !empty($trip['image_url']) ? $trip['image_url'] : 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80';
$maxPickableSeats = min(10, $remainingSeats);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Booking Process Header -->
<div class="py-3 bg-maroon text-white border-bottom border-warning">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span class="badge bg-warning text-dark fw-bold mb-1">BookMyShow Style Yatra Booking</span>
            <h4 class="fw-bold mb-0 text-white">
                <?= htmlspecialchars($trip['bus_name']) ?>
                <span class="fs-6 fw-normal text-warning ms-1">(<?= htmlspecialchars($trip['registration_number']) ?>)</span>
            </h4>
            <div class="small text-white-50 mt-1">
                <i class="bi bi-calendar-event me-1 text-warning"></i> <?= formatDate($trip['trip_date']) ?> at <?= formatTime($trip['departure_time']) ?> • 
                <i class="bi bi-geo-alt-fill me-1 text-warning"></i> <?= htmlspecialchars($settings['destination_name']) ?>
            </div>
        </div>

        <div class="text-md-end text-start">
            <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fs-6">
                <?= $remainingSeats ?> Seats Available
            </span>
            <div class="small text-white-50 mt-1">Fare: <strong><?= formatCurrency($trip['rate_per_seat']) ?></strong> / seat</div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row g-4">
        <!-- Main Form Column -->
        <div class="col-lg-8">
            <form id="bookMyShowForm">
                <input type="hidden" id="bmsTripId" value="<?= $trip['id'] ?>">
                <input type="hidden" id="bmsRatePerSeat" value="<?= $trip['rate_per_seat'] ?>">
                <input type="hidden" id="bmsAvailableSeats" value="<?= $remainingSeats ?>">

                <!-- STEP 1: SELECT NUMBER OF SEATS (BookMyShow Style Buttons) -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <span class="badge bg-danger text-white mb-1">Step 1</span>
                        <h5 class="fw-bold mb-0 text-navy">How Many Devotees / Seats?</h5>
                        <p class="text-muted small mb-0">Select the number of seats required for your pilgrimage journey.</p>
                    </div>

                    <div class="card-body p-4">
                        <div class="bms-seat-picker" id="seatPickerContainer">
                            <?php for ($s = 1; $s <= $maxPickableSeats; $s++): ?>
                                <button type="button" class="bms-seat-num-btn <?= ($s === 1) ? 'active' : '' ?>" data-seats="<?= $s ?>">
                                    <?= $s ?>
                                </button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" id="selectedSeatsCount" name="seats_count" value="1">
                        <div class="form-text small">
                            <i class="bi bi-info-circle me-1"></i> You can book up to <?= $maxPickableSeats ?> seats at a time.
                        </div>
                    </div>
                </div>

                <!-- STEP 2: SELECT PICKUP POINT -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <span class="badge bg-danger text-white mb-1">Step 2</span>
                        <h5 class="fw-bold mb-0 text-navy">Boarding / Pickup Point</h5>
                        <p class="text-muted small mb-0">Choose where all travelling devotees will board the bus.</p>
                    </div>

                    <div class="card-body p-4">
                        <label class="form-label small fw-bold">Select Boarding Stop <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" id="pickupPointSelect" name="pickup_point_id" required>
                            <option value="">-- Choose nearest pickup stop --</option>
                            <?php foreach ($pickupPoints as $p): ?>
                                <option value="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>">
                                    <?= htmlspecialchars($p['name']) ?> 
                                    <?= !empty($p['landmark']) ? '('.htmlspecialchars($p['landmark']).')' : '' ?>
                                    <?= !empty($p['pickup_time']) ? '— Departs: '.formatTime($p['pickup_time']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small mt-2">
                            <i class="bi bi-bus-front me-1 text-danger"></i> Bus departs to <?= htmlspecialchars($settings['destination_name']) ?> after collecting passengers at each stop.
                        </div>
                    </div>
                </div>

                <!-- STEP 3: NAMES OF TRAVELLING PERSONS (Dynamically Generated) -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <span class="badge bg-danger text-white mb-1">Step 3</span>
                        <h5 class="fw-bold mb-0 text-navy">Names of Travelling Devotees</h5>
                        <p class="text-muted small mb-0">Please enter the full name of each travelling person for the official temple boarding pass.</p>
                    </div>

                    <div class="card-body p-4">
                        <div id="travellersContainer" class="d-flex flex-column gap-3">
                            <!-- Populated dynamically by JavaScript -->
                            <div class="traveller-row">
                                <label class="form-label small fw-bold">Devotee 1 Name (Primary Passenger) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" class="form-control traveller-name-input" name="passenger_names[]" placeholder="Full name of devotee 1" value="<?= htmlspecialchars($customer['name'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 4 & 5: MOBILE NUMBER & OTP VERIFICATION -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <span class="badge bg-danger text-white mb-1">Step 4</span>
                        <h5 class="fw-bold mb-0 text-navy">Contact Mobile & OTP Verification</h5>
                        <p class="text-muted small mb-0">Your confirmed e-ticket and SMS/WhatsApp passes will be dispatched to this mobile number.</p>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label small fw-bold">10-Digit Mobile Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">+91</span>
                                    <input type="tel" class="form-control" id="contactPhoneInput" name="customer_phone" 
                                           placeholder="e.g. 9876543210" maxlength="15" 
                                           value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" 
                                           <?= $customer ? 'readonly' : '' ?> required>
                                    <?php if (!$customer): ?>
                                        <button class="btn btn-outline-danger fw-bold" type="button" id="btnRequestOtp">
                                            Send OTP
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-5 d-flex align-items-end">
                                <?php if ($customer): ?>
                                    <div class="badge bg-success-subtle text-success border border-success p-2 px-3 rounded-pill w-100 text-center">
                                        <i class="bi bi-check-circle-fill me-1"></i> Mobile Verified via OTP
                                    </div>
                                    <input type="hidden" id="otpVerifiedFlag" value="1">
                                <?php else: ?>
                                    <input type="hidden" id="otpVerifiedFlag" value="0">
                                    <div id="otpStatusBadge" class="small text-muted mb-2">
                                        Click "Send OTP" to verify your number.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- OTP Input Row (Appears when OTP is sent) -->
                        <div id="otpInputSection" class="mt-3 p-3 bg-light rounded-3 border d-none">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold mb-1">Enter 6-Digit OTP</label>
                                    <input type="text" class="form-control font-monospace letter-spacing-2 text-center fs-5" id="enteredOtpCode" placeholder="••••••" maxlength="6">
                                </div>
                                <div class="col-md-6 d-flex align-items-end pt-2 pt-md-0">
                                    <button type="button" class="btn btn-success fw-bold rounded-pill px-4 w-100" id="btnConfirmOtp">
                                        Verify & Confirm Mobile
                                    </button>
                                </div>
                            </div>
                            <div class="small text-muted mt-2">
                                <i class="bi bi-clock-history me-1"></i> OTP is valid for 10 minutes.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="text-end">
                    <button type="submit" class="btn btn-devotional btn-lg rounded-pill px-5 py-3 fw-bold shadow" id="btnSubmitYatraBooking">
                        <i class="bi bi-shield-lock-fill me-2"></i> Proceed to Secure Payment
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Column: Booking Breakdown Summary Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-top bg-white" style="top: 85px;">
                <div class="card-header bg-maroon text-white p-4 rounded-top-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="bi bi-receipt me-1 text-warning"></i> Yatra Summary</h5>
                        <span class="badge bg-warning text-dark fw-bold">Live Breakdown</span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Route Visual -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="small text-muted d-block">Boarding Stop:</div>
                        <strong class="text-navy d-block" id="summaryPickupText">Select boarding stop</strong>
                        <div class="border-start border-2 border-danger ms-2 ps-3 my-2 py-1 small text-muted">
                            <i class="bi bi-arrow-down text-danger"></i> Direct Pilgrimage Express
                        </div>
                        <div class="small text-muted d-block">Temple Destination:</div>
                        <strong class="text-danger d-block"><?= htmlspecialchars($settings['destination_name']) ?></strong>
                    </div>

                    <!-- Bus details -->
                    <div class="d-flex flex-column gap-2 mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Bus Service:</span>
                            <strong class="text-end text-dark"><?= htmlspecialchars($trip['bus_name']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Yatra Date:</span>
                            <strong><?= formatDate($trip['trip_date']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Departure:</span>
                            <strong><?= formatTime($trip['departure_time']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Devotees / Seats:</span>
                            <span class="badge bg-dark fs-6" id="summarySeatsCountBadge">1 Seat</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Rate Per Seat:</span>
                            <span><?= formatCurrency($trip['rate_per_seat']) ?></span>
                        </div>
                    </div>

                    <!-- Total Amount Box -->
                    <div class="price-summary-box text-center p-3 mb-3" style="background: #fff7ed; border-color: #fed7aa;">
                        <span class="text-muted small text-uppercase fw-bold d-block">Total Booking Fare</span>
                        <div class="display-6 fw-extrabold text-danger my-1" id="summaryTotalFareText">
                            <?= formatCurrency($trip['rate_per_seat']) ?>
                        </div>
                        <span class="badge bg-success-subtle text-success small">Tolls & Temple Transit Taxes Included</span>
                    </div>

                    <div class="small text-muted text-center">
                        <i class="bi bi-shield-check text-success me-1"></i> Instant digital boarding pass with barcode issued upon payment.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= ONLINE PAYMENT GATEWAY MODAL SIMULATOR ================= -->
<div class="modal fade" id="onlinePaymentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-maroon text-white border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="brand-icon" style="width: 32px; height: 32px; font-size: 1rem;">
                        <i class="bi bi-credit-card-2-front-fill"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0">Secure Online Payment</h6>
                        <small class="text-white-50">Gateway Checkout Sandbox</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 mb-3 border d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block">Amount Payable:</span>
                        <h3 class="fw-extrabold text-danger mb-0" id="modalPayAmount">₹ 0.00</h3>
                    </div>
                    <div class="text-end small">
                        <span class="text-muted d-block">Yatra Ref:</span>
                        <strong id="modalPayRef" class="badge bg-dark">--</strong>
                    </div>
                </div>

                <div class="small text-muted mb-3">
                    <span id="modalPayTrip">Trip details</span> • <span id="modalPayPassenger">Passenger</span>
                </div>

                <!-- Payment Methods Navigation Tabs -->
                <ul class="nav nav-pills nav-fill mb-3 gap-2" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active small py-2 rounded-3 border" data-bs-toggle="pill" data-bs-target="#card-pane" type="button">
                            <i class="bi bi-credit-card me-1"></i> Cards
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link small py-2 rounded-3 border" data-bs-toggle="pill" data-bs-target="#upi-pane" type="button">
                            <i class="bi bi-qr-code me-1"></i> UPI / QR
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link small py-2 rounded-3 border" data-bs-toggle="pill" data-bs-target="#netbanking-pane" type="button">
                            <i class="bi bi-bank me-1"></i> Net Banking
                        </button>
                    </li>
                </ul>

                <!-- Tab Contents -->
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="card-pane">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Card Number</label>
                            <input type="text" class="form-control font-monospace" value="4111 2222 3333 4444" placeholder="•••• •••• •••• ••••">
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-7">
                                <label class="form-label small fw-bold">Expiry</label>
                                <input type="text" class="form-control" value="12/28" placeholder="MM/YY">
                            </div>
                            <div class="col-5">
                                <label class="form-label small fw-bold">CVV</label>
                                <input type="password" class="form-control" value="888" placeholder="•••" maxlength="4">
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade text-center" id="upi-pane">
                        <div class="p-3 bg-light rounded-3 mb-2 border">
                            <i class="bi bi-qr-code-scan display-4 text-danger d-block mb-2"></i>
                            <span class="small fw-bold d-block">Scan with PhonePe, GPay, Paytm, BHIM</span>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="netbanking-pane">
                        <label class="form-label small fw-bold">Select Bank</label>
                        <select class="form-select mb-2">
                            <option>State Bank of India (SBI)</option>
                            <option>HDFC Bank</option>
                            <option>ICICI Bank</option>
                            <option>Axis Bank</option>
                        </select>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small mt-3 mb-0">
                    <i class="bi bi-info-circle-fill me-1"></i> <strong>Simulation / Sandbox Mode:</strong> Clicking "Pay & Confirm" will verify the payment, confirm all devotee seats, and issue your official Yatra pass.
                </div>
            </div>

            <div class="modal-footer bg-light border-0 p-3">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success rounded-pill px-4 fw-bold" id="btnConfirmMockPayment">
                    <i class="bi bi-check2-circle me-1"></i> Pay & Confirm Booking
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tripId = parseInt(document.getElementById('bmsTripId').value);
    const ratePerSeat = parseFloat(document.getElementById('bmsRatePerSeat').value);
    const availableSeats = parseInt(document.getElementById('bmsAvailableSeats').value);
    let selectedSeats = 1;

    const seatButtons = document.querySelectorAll('.bms-seat-num-btn');
    const selectedSeatsInput = document.getElementById('selectedSeatsCount');
    const travellersContainer = document.getElementById('travellersContainer');
    const pickupSelect = document.getElementById('pickupPointSelect');
    const summaryPickupText = document.getElementById('summaryPickupText');
    const summarySeatsCountBadge = document.getElementById('summarySeatsCountBadge');
    const summaryTotalFareText = document.getElementById('summaryTotalFareText');

    // Seat Picker Click
    seatButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            seatButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            selectedSeats = parseInt(this.dataset.seats);
            selectedSeatsInput.value = selectedSeats;

            updateDevoteeInputs(selectedSeats);
            updateSummary();
        });
    });

    // Pickup Point Change
    pickupSelect?.addEventListener('change', function () {
        const text = this.options[this.selectedIndex]?.dataset.name || 'Select boarding stop';
        summaryPickupText.textContent = text;
    });

    // Update Devotee Name Inputs Dynamically
    function updateDevoteeInputs(count) {
        // Collect currently typed names so we don't lose them
        const existingInputs = document.querySelectorAll('.traveller-name-input');
        const existingNames = [];
        existingInputs.forEach(inp => existingNames.push(inp.value));

        let html = '';
        for (let i = 1; i <= count; i++) {
            const currentVal = existingNames[i - 1] || '';
            const label = (i === 1) ? 'Devotee 1 Name (Primary Passenger)' : `Devotee ${i} Name`;
            html += `
                <div class="traveller-row">
                    <label class="form-label small fw-bold">${label} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person-fill"></i></span>
                        <input type="text" class="form-control traveller-name-input" name="passenger_names[]" placeholder="Full name of devotee ${i}" value="${currentVal}" required>
                    </div>
                </div>
            `;
        }
        travellersContainer.innerHTML = html;
    }

    // Update Summary Box
    function updateSummary() {
        summarySeatsCountBadge.textContent = `${selectedSeats} Devotee(s)`;
        const total = (selectedSeats * ratePerSeat);
        summaryTotalFareText.textContent = `${CURRENCY_SYMBOL} ${total.toFixed(2)}`;
    }

    // --- OTP Generation & Verification Logic ---
    const btnRequestOtp = document.getElementById('btnRequestOtp');
    const contactPhoneInput = document.getElementById('contactPhoneInput');
    const otpInputSection = document.getElementById('otpInputSection');
    const enteredOtpCode = document.getElementById('enteredOtpCode');
    const btnConfirmOtp = document.getElementById('btnConfirmOtp');
    const otpVerifiedFlag = document.getElementById('otpVerifiedFlag');
    const otpStatusBadge = document.getElementById('otpStatusBadge');

    if (btnRequestOtp) {
        btnRequestOtp.addEventListener('click', function () {
            const phone = contactPhoneInput.value.trim();
            if (phone.length < 10) {
                showToast('Invalid Mobile Number', 'Please enter a valid 10-digit mobile number.', 'warning');
                contactPhoneInput.focus();
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending OTP...';

            const fd = new FormData();
            fd.append('phone', phone);
            fd.append('type', 'booking');

            fetch(`${BASE_URL}/api/send_otp.php`, {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                btnRequestOtp.disabled = false;
                btnRequestOtp.textContent = 'Resend OTP';

                if (data.success) {
                    otpInputSection.classList.remove('d-none');
                    if (otpStatusBadge) {
                        otpStatusBadge.innerHTML = `<span class="text-primary fw-bold"><i class="bi bi-chat-dots me-1"></i> OTP dispatched to ${phone}</span>`;
                    }

                    // Show simulation popup
                    if (data.data?.debug_otp) {
                        showNotificationToast('sms', `[SIMULATED SMS - ${phone}] Your Divya Yatra verification OTP is: ${data.data.debug_otp}. Valid for 10 minutes.`);
                        if (enteredOtpCode) enteredOtpCode.value = data.data.debug_otp;
                    }
                } else {
                    showToast('OTP Error', data.message, 'danger');
                }
            })
            .catch(() => {
                btnRequestOtp.disabled = false;
                btnRequestOtp.textContent = 'Send OTP';
                showToast('Network Error', 'Failed to send OTP. Please check your connection.', 'danger');
            });
        });
    }

    if (btnConfirmOtp) {
        btnConfirmOtp.addEventListener('click', function () {
            const phone = contactPhoneInput.value.trim();
            const code = enteredOtpCode.value.trim();
            const firstDevoteeName = document.querySelector('.traveller-name-input')?.value.trim() || 'Devotee';

            if (!code) {
                showToast('OTP Required', 'Please enter the 6-digit code received on your phone.', 'warning');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Verifying...';

            const fd = new FormData();
            fd.append('phone', phone);
            fd.append('otp_code', code);
            fd.append('name', firstDevoteeName);

            fetch(`${BASE_URL}/api/verify_otp.php`, {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                btnConfirmOtp.disabled = false;
                btnConfirmOtp.textContent = 'Verify & Confirm Mobile';

                if (data.success) {
                    otpVerifiedFlag.value = '1';
                    contactPhoneInput.readOnly = true;
                    if (btnRequestOtp) btnRequestOtp.classList.add('d-none');
                    otpInputSection.classList.add('d-none');
                    if (otpStatusBadge) {
                        otpStatusBadge.innerHTML = `<span class="badge bg-success-subtle text-success border border-success p-2 px-3 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Mobile Verified (${phone})</span>`;
                    }
                    showToast('Verified', 'Mobile number verified successfully!', 'success');
                } else {
                    showToast('Invalid OTP', data.message, 'danger');
                }
            })
            .catch(() => {
                btnConfirmOtp.disabled = false;
                btnConfirmOtp.textContent = 'Verify & Confirm Mobile';
                showToast('Error', 'OTP verification failed.', 'danger');
            });
        });
    }

    // --- Form Submit -> Create Pending Booking & Open Payment Gateway ---
    const bookingForm = document.getElementById('bookMyShowForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate pickup point
            const pickupId = pickupSelect.value;
            if (!pickupId) {
                showToast('Select Boarding Stop', 'Please select where you will board the bus.', 'warning');
                pickupSelect.focus();
                return;
            }

            // Validate passenger names
            const nameInputs = document.querySelectorAll('.traveller-name-input');
            const devoteeNames = [];
            let allNamesFilled = true;
            nameInputs.forEach((inp, idx) => {
                const val = inp.value.trim();
                if (!val) {
                    allNamesFilled = false;
                    inp.focus();
                } else {
                    devoteeNames.push(val);
                }
            });

            if (!allNamesFilled) {
                showToast('Passenger Names Required', 'Please fill the full name for all devotees.', 'warning');
                return;
            }

            // Validate mobile & OTP
            const phone = contactPhoneInput.value.trim();
            if (phone.length < 10) {
                showToast('Mobile Required', 'Please enter your 10-digit mobile number.', 'warning');
                contactPhoneInput.focus();
                return;
            }

            if (otpVerifiedFlag.value !== '1') {
                showToast('Verify Mobile Number', 'Please click "Send OTP" and verify your mobile number before proceeding.', 'warning');
                if (btnRequestOtp) btnRequestOtp.focus();
                return;
            }

            const submitBtn = document.getElementById('btnSubmitYatraBooking');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Initiating Secure Payment...';

            const fd = new FormData();
            fd.append('trip_id', tripId);
            fd.append('pickup_point_id', pickupId);
            fd.append('seats_count', selectedSeats);
            fd.append('customer_name', devoteeNames[0]);
            fd.append('customer_phone', phone);
            devoteeNames.forEach(n => fd.append('passenger_names[]', n));

            fetch(`${BASE_URL}/api/create_booking.php`, {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(orderData => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-shield-lock-fill me-2"></i> Proceed to Secure Payment';

                if (orderData.success) {
                    openPaymentModal(orderData.data, devoteeNames);
                } else {
                    showToast('Booking Error', orderData.message, 'danger');
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-shield-lock-fill me-2"></i> Proceed to Secure Payment';
                showToast('Server Error', 'Failed to communicate with payment processor.', 'danger');
            });
        });
    }

    // Open Payment Gateway Modal
    function openPaymentModal(data, names) {
        document.getElementById('modalPayAmount').textContent = data.amount_formatted;
        document.getElementById('modalPayRef').textContent = data.booking_ref;
        document.getElementById('modalPayPassenger').textContent = names.join(', ');
        document.getElementById('modalPayTrip').textContent = `${data.bus_name} • ${data.seats} Devotee(s)`;

        const modalEl = document.getElementById('onlinePaymentModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        const btnConfirm = document.getElementById('btnConfirmMockPayment');
        btnConfirm.onclick = function () {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Confirming Gateway Transaction...';

            setTimeout(() => {
                const payFd = new FormData();
                payFd.append('booking_id', data.booking_id);
                payFd.append('transaction_ref', data.payment_order.transaction_ref);
                payFd.append('status', 'success');
                payFd.append('payment_method', 'online_gateway');

                fetch(`${BASE_URL}/api/verify_payment.php`, {
                    method: 'POST',
                    body: payFd
                })
                .then(r => r.json())
                .then(confirmData => {
                    if (confirmData.success) {
                        showNotificationToast('sms', `Har Har Mahadev! Payment Verified: ${data.booking_ref}. Sacred Yatra passes confirmed for ${data.seats} devotee(s). Show ticket pass upon boarding.`);
                        setTimeout(() => {
                            window.location.href = confirmData.data.redirect_url;
                        }, 1000);
                    } else {
                        btnConfirm.disabled = false;
                        btnConfirm.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Pay & Confirm Booking';
                        showToast('Payment Verification Failed', confirmData.message, 'danger');
                    }
                })
                .catch(() => {
                    btnConfirm.disabled = false;
                    btnConfirm.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Pay & Confirm Booking';
                    showToast('Error', 'Payment failed.', 'danger');
                });
            }, 1200);
        };
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
