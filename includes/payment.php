<?php
/**
 * Payment Gateway Handler: Razorpay & Built-in Sandbox Simulator
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notification.php';

class PaymentGateway {

    /**
     * Create a payment order/session for a booking
     */
    public static function initiatePayment($bookingId, $amount, $currency = 'INR') {
        $db = getDB();
        $settings = getSystemSettings();
        $mode = $settings['payment_gateway_mode'] ?? 'sandbox';

        $txRef = 'TXN-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        // Insert payment log
        $stmt = $db->prepare("
            INSERT INTO `payments` (`booking_id`, `transaction_ref`, `amount`, `gateway`, `status`, `created_at`)
            VALUES (?, ?, ?, ?, 'initiated', NOW())
        ");
        $gatewayName = ($mode === 'live') ? 'razorpay' : 'razorpay_simulator';
        $stmt->execute([$bookingId, $txRef, $amount, $gatewayName]);
        $paymentId = $db->lastInsertId();

        $orderData = [
            'payment_id' => $paymentId,
            'transaction_ref' => $txRef,
            'amount' => $amount,
            'currency' => $currency,
            'mode' => $mode,
            'razorpay_key_id' => $settings['razorpay_key_id'] ?? 'rzp_test_sampleKey123'
        ];

        return $orderData;
    }

    /**
     * Verify payment status and complete booking confirmation
     */
    public static function verifyAndConfirmBooking($bookingId, $transactionRef, $gatewayStatus = 'success', $rawResponse = []) {
        $db = getDB();
        
        try {
            $db->beginTransaction();

            // Lock booking row
            $stmt = $db->prepare("SELECT * FROM `bookings` WHERE id = ? FOR UPDATE");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Booking not found'];
            }

            if ($booking['payment_status'] === 'paid') {
                $db->rollBack();
                return ['success' => true, 'message' => 'Booking already confirmed', 'booking' => $booking];
            }

            if ($gatewayStatus !== 'success') {
                // Payment failed
                $stmt = $db->prepare("
                    UPDATE `bookings` 
                    SET `payment_status` = 'failed', `booking_status` = 'cancelled', `transaction_ref` = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$transactionRef, $bookingId]);

                $stmtPay = $db->prepare("
                    UPDATE `payments` 
                    SET `status` = 'failed', `gateway_response` = ? 
                    WHERE `booking_id` = ? AND `transaction_ref` = ?
                ");
                $stmtPay->execute([json_encode($rawResponse), $bookingId, $transactionRef]);

                $db->commit();
                return ['success' => false, 'message' => 'Payment failed or declined'];
            }

            // Check trip capacity before confirming
            $stmtTrip = $db->prepare("SELECT * FROM `trips` WHERE id = ? FOR UPDATE");
            $stmtTrip->execute([$booking['trip_id']]);
            $trip = $stmtTrip->fetch();

            if (!$trip) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Trip not found'];
            }

            $availableSeats = $trip['total_capacity'] - $trip['booked_seats'];
            if ($availableSeats < $booking['seats_count']) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Sorry, insufficient seats available for this trip!'];
            }

            // 1. Update Booking status to PAID & CONFIRMED
            $stmtBook = $db->prepare("
                UPDATE `bookings` 
                SET `payment_status` = 'paid', `booking_status` = 'confirmed', `transaction_ref` = ?, `updated_at` = NOW() 
                WHERE id = ?
            ");
            $stmtBook->execute([$transactionRef, $bookingId]);

            // 2. Update Payment record
            $stmtPay = $db->prepare("
                UPDATE `payments` 
                SET `status` = 'success', `gateway_response` = ? 
                WHERE `booking_id` = ? AND `transaction_ref` = ?
            ");
            $stmtPay->execute([json_encode($rawResponse), $bookingId, $transactionRef]);

            // 3. Atomically update Trip booked_seats
            $newBookedSeats = $trip['booked_seats'] + $booking['seats_count'];
            $stmtUpdateTrip = $db->prepare("UPDATE `trips` SET `booked_seats` = ? WHERE id = ?");
            $stmtUpdateTrip->execute([$newBookedSeats, $trip['id']]);

            $db->commit();

            // Fetch detailed booking for notifications
            $stmtDetails = $db->prepare("
                SELECT b.*, t.trip_date, t.departure_time, t.total_capacity, bs.name as bus_name, bs.registration_number, p.name as pickup_name
                FROM `bookings` b
                JOIN `trips` t ON b.trip_id = t.id
                JOIN `buses` bs ON t.bus_id = bs.id
                JOIN `pickup_points` p ON b.pickup_point_id = p.id
                WHERE b.id = ?
            ");
            $stmtDetails->execute([$bookingId]);
            $fullBooking = $stmtDetails->fetch();

            // Trigger Notifications
            NotificationService::notifyCustomerBookingConfirmed($fullBooking);
            NotificationService::notifyAdminNewBooking($fullBooking);

            // Trigger 70% Occupancy Check & Alert
            NotificationService::checkAndTriggerOccupancyAlert($trip['id']);

            return [
                'success' => true,
                'message' => 'Booking successfully confirmed!',
                'booking' => $fullBooking
            ];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'message' => 'Payment confirmation error: ' . $e->getMessage()];
        }
    }

    /**
     * Verify Razorpay Webhook or Signature
     */
    public static function verifyRazorpaySignature($orderId, $paymentId, $signature) {
        $settings = getSystemSettings();
        $secret = $settings['razorpay_key_secret'] ?? '';
        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);
        return hash_equals($expectedSignature, $signature);
    }
}
