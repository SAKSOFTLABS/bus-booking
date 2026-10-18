/**
 * Customer Interface JavaScript - Booking, OTP, Calculations & Payment Simulator
 */

document.addEventListener('DOMContentLoaded', function () {
    // Current booking state
    const bookingState = {
        tripId: null,
        ratePerSeat: 0,
        totalCapacity: 0,
        availableSeats: 0,
        selectedSeats: 1,
        pickupPointId: null,
        pickupName: '',
        customerName: '',
        customerPhone: '',
        bookingId: null,
        bookingRef: null
    };

    // DOM Elements
    const tripCards = document.querySelectorAll('.trip-card');
    const selectedTripInput = document.getElementById('selectedTripId');
    const pickupSelect = document.getElementById('pickupPointSelect');
    const seatsInput = document.getElementById('seatsCountInput');
    const btnDecreaseSeats = document.getElementById('btnDecreaseSeats');
    const btnIncreaseSeats = document.getElementById('btnIncreaseSeats');
    const summaryTripName = document.getElementById('summaryTripName');
    const summaryPickupName = document.getElementById('summaryPickupName');
    const summarySeatsCount = document.getElementById('summarySeatsCount');
    const summaryRatePerSeat = document.getElementById('summaryRatePerSeat');
    const summaryTotalAmount = document.getElementById('summaryTotalAmount');
    const summaryAvailableBadge = document.getElementById('summaryAvailableBadge');
    const bookingForm = document.getElementById('bookingDetailsForm');

    // Trip Card Selection Handler
    tripCards.forEach(card => {
        card.addEventListener('click', function () {
            if (this.classList.contains('sold-out')) return;

            tripCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            bookingState.tripId = parseInt(this.dataset.tripId);
            bookingState.ratePerSeat = parseFloat(this.dataset.rate);
            bookingState.totalCapacity = parseInt(this.dataset.capacity);
            bookingState.availableSeats = parseInt(this.dataset.available);

            if (selectedTripInput) selectedTripInput.value = bookingState.tripId;

            // Enforce seat limit if current selected seats exceed available
            if (bookingState.selectedSeats > bookingState.availableSeats) {
                bookingState.selectedSeats = Math.max(1, bookingState.availableSeats);
                if (seatsInput) seatsInput.value = bookingState.selectedSeats;
            }

            // Update UI Summary
            updateSummaryView(this.dataset.busName, this.dataset.departureTime);
            updatePriceCalculation();

            // Enable Step 2 fields
            if (pickupSelect) pickupSelect.disabled = false;
            if (seatsInput) {
                seatsInput.disabled = false;
                seatsInput.max = bookingState.availableSeats;
            }
            if (btnDecreaseSeats) btnDecreaseSeats.disabled = false;
            if (btnIncreaseSeats) btnIncreaseSeats.disabled = false;

            // Scroll smoothly towards step 2 on mobile
            if (window.innerWidth < 768) {
                document.getElementById('step-2-section')?.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Seat Counter Handlers
    if (btnDecreaseSeats) {
        btnDecreaseSeats.addEventListener('click', function () {
            if (bookingState.selectedSeats > 1) {
                bookingState.selectedSeats--;
                if (seatsInput) seatsInput.value = bookingState.selectedSeats;
                updatePriceCalculation();
            }
        });
    }

    if (btnIncreaseSeats) {
        btnIncreaseSeats.addEventListener('click', function () {
            if (bookingState.selectedSeats < bookingState.availableSeats) {
                bookingState.selectedSeats++;
                if (seatsInput) seatsInput.value = bookingState.selectedSeats;
                updatePriceCalculation();
            } else {
                showToast('Maximum Seats Reached', `Only ${bookingState.availableSeats} seats remaining for this trip.`, 'warning');
            }
        });
    }

    if (seatsInput) {
        seatsInput.addEventListener('change', function () {
            let val = parseInt(this.value) || 1;
            if (val < 1) val = 1;
            if (val > bookingState.availableSeats) {
                val = bookingState.availableSeats;
                showToast('Seat Limit', `Adjusted to maximum available seats (${bookingState.availableSeats}).`, 'info');
            }
            bookingState.selectedSeats = val;
            this.value = val;
            updatePriceCalculation();
        });
    }

    // Pickup Point Change Handler
    if (pickupSelect) {
        pickupSelect.addEventListener('change', function () {
            bookingState.pickupPointId = this.value;
            bookingState.pickupName = this.options[this.selectedIndex]?.text || '';
            if (summaryPickupName) summaryPickupName.textContent = bookingState.pickupName || 'Not Selected';
        });
    }

    // Update Summary Box
    function updateSummaryView(busName, depTime) {
        if (summaryTripName) summaryTripName.textContent = `${busName} (${depTime})`;
        if (summaryAvailableBadge) {
            summaryAvailableBadge.textContent = `${bookingState.availableSeats} Seats Available`;
            summaryAvailableBadge.className = bookingState.availableSeats <= 10 
                ? 'badge bg-danger text-white' 
                : 'badge bg-success-subtle text-success border border-success';
        }
        if (summaryRatePerSeat) summaryRatePerSeat.textContent = `${CURRENCY_SYMBOL} ${bookingState.ratePerSeat.toFixed(2)}`;
    }

    // Update Price Calculation
    function updatePriceCalculation() {
        const total = (bookingState.ratePerSeat * bookingState.selectedSeats);
        if (summarySeatsCount) summarySeatsCount.textContent = bookingState.selectedSeats;
        if (summaryTotalAmount) summaryTotalAmount.textContent = `${CURRENCY_SYMBOL} ${total.toFixed(2)}`;
    }

    // Booking Details Form Submit (Initiate Payment)
    if (bookingForm) {
        bookingForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!bookingState.tripId) {
                showToast('Select a Trip', 'Please select an available bus trip to continue.', 'danger');
                return;
            }

            const pickupId = pickupSelect?.value;
            if (!pickupId) {
                showToast('Select Pickup Point', 'Please choose your pickup point.', 'danger');
                pickupSelect?.focus();
                return;
            }

            const custNameInput = document.getElementById('customerNameInput');
            const custPhoneInput = document.getElementById('customerPhoneInput');
            const custName = custNameInput?.value.trim();
            const custPhone = custPhoneInput?.value.trim();

            if (!custName || !custPhone) {
                showToast('Contact Required', 'Please enter your name and 10-digit mobile number.', 'danger');
                return;
            }

            const submitBtn = document.getElementById('btnProceedToPayment');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Preparing Payment...';
            }

            // Create Pending Booking via AJAX
            const formData = new FormData();
            formData.append('trip_id', bookingState.tripId);
            formData.append('pickup_point_id', pickupId);
            formData.append('seats_count', bookingState.selectedSeats);
            formData.append('customer_name', custName);
            formData.append('customer_phone', custPhone);

            fetch(`${BASE_URL}/api/create_booking.php`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-shield-lock-fill me-2"></i>Proceed to Secure Payment';
                }

                if (data.success) {
                    bookingState.bookingId = data.data.booking_id;
                    bookingState.bookingRef = data.data.booking_ref;
                    openPaymentModal(data.data);
                } else {
                    showToast('Booking Error', data.message, 'danger');
                }
            })
            .catch(err => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-shield-lock-fill me-2"></i>Proceed to Secure Payment';
                }
                showToast('Network Error', 'Failed to communicate with server. Please try again.', 'danger');
            });
        });
    }

    // --- Online Payment Gateway Modal Logic ---
    function openPaymentModal(orderData) {
        const modalEl = document.getElementById('onlinePaymentModal');
        if (!modalEl) return;

        // Populate order details in modal
        document.getElementById('modalPayAmount').textContent = orderData.amount_formatted;
        document.getElementById('modalPayRef').textContent = orderData.booking_ref;
        document.getElementById('modalPayPassenger').textContent = `${orderData.customer_name} (${orderData.customer_phone})`;
        document.getElementById('modalPayTrip').textContent = `${orderData.bus_name} • ${orderData.seats} Seat(s)`;

        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Payment Simulation Submit
        const btnConfirmMockPay = document.getElementById('btnConfirmMockPayment');
        if (btnConfirmMockPay) {
            btnConfirmMockPay.onclick = function () {
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying Gateway Transaction...';

                const selectedMethod = document.querySelector('input[name="payment_method_choice"]:checked')?.value || 'card';

                // Simulate realistic 1.2s gateway verification delay
                setTimeout(() => {
                    const payFormData = new FormData();
                    payFormData.append('booking_id', orderData.booking_id);
                    payFormData.append('transaction_ref', orderData.payment_order.transaction_ref);
                    payFormData.append('status', 'success');
                    payFormData.append('payment_method', selectedMethod);

                    fetch(`${BASE_URL}/api/verify_payment.php`, {
                        method: 'POST',
                        body: payFormData
                    })
                    .then(res => res.json())
                    .then(verifyData => {
                        if (verifyData.success) {
                            // Show success toast simulation
                            showNotificationToast('sms', `Payment Verified: ${orderData.booking_ref}. Booking confirmed for ${orderData.seats} seat(s). Check your ticket!`);
                            setTimeout(() => {
                                window.location.href = verifyData.data.redirect_url;
                            }, 1000);
                        } else {
                            btnConfirmMockPay.disabled = false;
                            btnConfirmMockPay.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Pay & Confirm Booking';
                            showToast('Payment Verification Failed', verifyData.message, 'danger');
                        }
                    })
                    .catch(e => {
                        btnConfirmMockPay.disabled = false;
                        btnConfirmMockPay.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Pay & Confirm Booking';
                        showToast('Error', 'Payment confirmation failed. Try again.', 'danger');
                    });
                }, 1200);
            };
        }
    }

    // --- OTP Login / Verification Modal Logic ---
    const btnSendOtp = document.getElementById('btnSendOtpModal');
    const btnVerifyOtp = document.getElementById('btnVerifyOtpModal');
    const otpPhoneInput = document.getElementById('otpModalPhone');
    const otpCodeInput = document.getElementById('otpModalCode');
    const otpStep1 = document.getElementById('otpStep1');
    const otpStep2 = document.getElementById('otpStep2');

    if (btnSendOtp && otpPhoneInput) {
        btnSendOtp.addEventListener('click', function () {
            const phone = otpPhoneInput.value.trim();
            if (phone.length < 10) {
                showToast('Invalid Phone', 'Please enter a valid 10-digit mobile number.', 'warning');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...';

            const fd = new FormData();
            fd.append('phone', phone);
            fd.append('type', 'login');

            fetch(`${BASE_URL}/api/send_otp.php`, {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                btnSendOtp.disabled = false;
                btnSendOtp.textContent = 'Send OTP';

                if (data.success) {
                    otpStep1.classList.add('d-none');
                    otpStep2.classList.remove('d-none');
                    document.getElementById('otpSentToPhone').textContent = phone;
                    
                    // Show simulated SMS toast for easy testing!
                    if (data.data?.debug_otp) {
                        showNotificationToast('sms', `[SIMULATED SMS] Your TransitGo OTP code is: ${data.data.debug_otp}. Valid for 10 minutes.`);
                        if (otpCodeInput) otpCodeInput.value = data.data.debug_otp;
                    }
                } else {
                    showToast('Failed to Send OTP', data.message, 'danger');
                }
            })
            .catch(() => {
                btnSendOtp.disabled = false;
                btnSendOtp.textContent = 'Send OTP';
                showToast('Error', 'Failed to send OTP. Please try again.', 'danger');
            });
        });
    }

    if (btnVerifyOtp && otpCodeInput) {
        btnVerifyOtp.addEventListener('click', function () {
            const phone = otpPhoneInput.value.trim();
            const otp = otpCodeInput.value.trim();

            if (!otp) {
                showToast('OTP Required', 'Please enter the 6-digit OTP code received.', 'warning');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Verifying...';

            const fd = new FormData();
            fd.append('phone', phone);
            fd.append('otp_code', otp);

            fetch(`${BASE_URL}/api/verify_otp.php`, {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', 'Logged in successfully! Reloading...', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    btnVerifyOtp.disabled = false;
                    btnVerifyOtp.textContent = 'Verify & Login';
                    showToast('Invalid OTP', data.message, 'danger');
                }
            })
            .catch(() => {
                btnVerifyOtp.disabled = false;
                btnVerifyOtp.textContent = 'Verify & Login';
                showToast('Error', 'Failed to verify OTP.', 'danger');
            });
        });
    }
});

// Helper: General Toast Message
function showToast(title, message, type = 'info') {
    const container = document.getElementById('notificationToastContainer');
    if (!container) return;

    const toastId = 'toast-' + Math.random().toString(36).substr(2, 9);
    const bgMap = {
        success: 'text-bg-success',
        danger: 'text-bg-danger',
        warning: 'text-bg-warning text-dark',
        info: 'text-bg-primary'
    };
    const bgClass = bgMap[type] || 'text-bg-dark';

    const html = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}:</strong> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
    const toastEl = document.getElementById(toastId);
    const bsToast = new bootstrap.Toast(toastEl, { delay: 4500 });
    bsToast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

// Helper: Simulated SMS / WhatsApp notification toast popup
function showNotificationToast(channel, message) {
    const container = document.getElementById('notificationToastContainer');
    if (!container) return;

    const toastId = 'notify-toast-' + Math.random().toString(36).substr(2, 9);
    const isWhatsApp = channel === 'whatsapp';
    const icon = isWhatsApp ? 'bi-whatsapp text-success' : 'bi-chat-left-text-fill text-primary';
    const title = isWhatsApp ? 'Simulated WhatsApp Message' : 'Simulated SMS Message';

    const html = `
        <div id="${toastId}" class="toast simulated-notification-toast ${channel} bg-white shadow-lg border" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-light">
                <i class="bi ${icon} me-2 fs-5"></i>
                <strong class="me-auto text-dark">${title}</strong>
                <small class="text-muted">Just now</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body text-dark small" style="white-space: pre-line;">
                ${message}
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
    const toastEl = document.getElementById(toastId);
    const bsToast = new bootstrap.Toast(toastEl, { delay: 7000 });
    bsToast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}
