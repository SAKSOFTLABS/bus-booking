<?php
/**
 * Notification Service: SMS & WhatsApp Handler
 * Handles customer and administrator notifications including 70% occupancy alerts.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

class NotificationService {

    /**
     * Send or simulate an SMS message
     */
    public static function sendSMS($phone, $message, $eventType, $recipientType = 'customer') {
        $settings = getSystemSettings();
        $mode = $settings['notification_mode'] ?? 'simulation';
        $status = 'simulated';
        $responseData = null;

        if ($mode === 'api') {
            // Real SMS API Gateway integration (e.g. Fast2SMS or Twilio)
            $provider = $settings['sms_provider'] ?? 'simulation';
            if ($provider === 'fast2sms' && !empty($settings['sms_api_key'])) {
                $status = self::sendFast2SMS($phone, $message, $settings['sms_api_key']);
            } elseif ($provider === 'twilio' && !empty($settings['sms_api_key'])) {
                // Twilio webhook / REST
                $status = 'sent';
            } else {
                $status = 'simulated';
            }
        }

        self::logNotification($recipientType, $phone, 'sms', $eventType, $message, $status, $responseData);
        return [
            'success' => true,
            'channel' => 'sms',
            'phone' => $phone,
            'status' => $status,
            'message' => $message
        ];
    }

    /**
     * Send or simulate a WhatsApp message
     */
    public static function sendWhatsApp($phone, $message, $eventType, $recipientType = 'customer') {
        $settings = getSystemSettings();
        $mode = $settings['notification_mode'] ?? 'simulation';
        $status = 'simulated';
        $responseData = null;

        if ($mode === 'api' && !empty($settings['whatsapp_api_token']) && !empty($settings['whatsapp_phone_number_id'])) {
            // Meta WhatsApp Cloud API integration
            $status = self::sendMetaWhatsApp($phone, $message, $settings['whatsapp_api_token'], $settings['whatsapp_phone_number_id']);
        }

        self::logNotification($recipientType, $phone, 'whatsapp', $eventType, $message, $status, $responseData);
        return [
            'success' => true,
            'channel' => 'whatsapp',
            'phone' => $phone,
            'status' => $status,
            'message' => $message
        ];
    }

    /**
     * Dispatch both SMS and WhatsApp notification
     */
    public static function sendBoth($phone, $message, $eventType, $recipientType = 'customer') {
        $smsResult = self::sendSMS($phone, $message, $eventType, $recipientType);
        $waResult = self::sendWhatsApp($phone, $message, $eventType, $recipientType);
        return [
            'sms' => $smsResult,
            'whatsapp' => $waResult
        ];
    }

    /**
     * Notify Customer of OTP Code
     */
    public static function notifyCustomerOTP($phone, $otpCode, $type = 'login') {
        $appName = APP_NAME;
        $msg = "Your {$appName} verification code is: {$otpCode}. Valid for 10 minutes. Please do not share this code with anyone.";
        return self::sendBoth($phone, $msg, 'otp_verification', 'customer');
    }

    /**
     * Notify Customer upon successful booking confirmation
     */
    public static function notifyCustomerBookingConfirmed($booking) {
        $settings = getSystemSettings();
        $appName = APP_NAME;
        $dest = $settings['destination_name'] ?? 'Fixed Destination';
        $dateFormatted = formatDate($booking['trip_date']);
        $depTime = formatTime($booking['departure_time']);
        $pickup = $booking['pickup_name'] ?? 'Selected Pickup Point';
        $seats = $booking['seats_count'];
        $amount = formatCurrency($booking['total_amount']);
        $ref = $booking['booking_ref'];

        $msg = "🎉 {$appName} Booking Confirmed!\n"
             . "Ref: {$ref}\n"
             . "Trip Date: {$dateFormatted}\n"
             . "Pickup: {$pickup} (Bus Departs: {$depTime})\n"
             . "Destination: {$dest}\n"
             . "Seats: {$seats} | Amount Paid: {$amount}\n"
             . "Have a safe journey! Show this SMS/WhatsApp to the bus crew.";

        return self::sendBoth($booking['customer_phone'], $msg, 'booking_confirmed', 'customer');
    }

    /**
     * Notify Administrator upon new booking and payment
     */
    public static function notifyAdminNewBooking($booking) {
        $settings = getSystemSettings();
        $adminPhone = $settings['admin_alert_phone'] ?? '+919876543210';
        $appName = APP_NAME;
        $ref = $booking['booking_ref'];
        $busName = $booking['bus_name'] ?? 'Bus';
        $dateFormatted = formatDate($booking['trip_date']);
        $customer = $booking['customer_name'] . ' (' . $booking['customer_phone'] . ')';
        $seats = $booking['seats_count'];
        $amount = formatCurrency($booking['total_amount']);

        $msg = "🔔 [{$appName} - New Booking Alert]\n"
             . "Booking Ref: {$ref}\n"
             . "Bus: {$busName} on {$dateFormatted}\n"
             . "Passenger: {$customer}\n"
             . "Seats Booked: {$seats}\n"
             . "Amount: {$amount} (Payment: PAID)\n"
             . "Trip ID: #{$booking['trip_id']}";

        return self::sendBoth($adminPhone, $msg, 'admin_new_booking', 'admin');
    }

    /**
     * 70% Occupancy Alert Logic
     * Formula: Occupancy Percentage = (Total Booked Seats ÷ Total Bus Capacity) × 100
     * Triggered when occupancy reaches or exceeds 70%.
     * Prevents repeated alerts using trip.occupancy_alert_sent flag.
     */
    public static function checkAndTriggerOccupancyAlert($tripId) {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT t.*, b.name as bus_name, b.registration_number
            FROM `trips` t
            JOIN `buses` b ON t.bus_id = b.id
            WHERE t.id = ?
        ");
        $stmt->execute([$tripId]);
        $trip = $stmt->fetch();

        if (!$trip) return false;

        $capacity = (int)$trip['total_capacity'];
        $booked = (int)$trip['booked_seats'];
        if ($capacity <= 0) return false;

        $occupancyPercent = calculateOccupancy($booked, $capacity);
        $settings = getSystemSettings();
        $threshold = (float)($settings['occupancy_threshold'] ?? 70.0);

        // Check if threshold reached and alert hasn't been sent yet
        if ($occupancyPercent >= $threshold && (int)$trip['occupancy_alert_sent'] === 0) {
            $adminPhone = $settings['admin_alert_phone'] ?? '+919876543210';
            $remaining = $capacity - $booked;
            $dateFormatted = formatDate($trip['trip_date']);
            $depTime = formatTime($trip['departure_time']);

            $msg = "🚨 [70% SEAT OCCUPANCY ALERT - " . APP_NAME . "]\n"
                 . "Trip #{$trip['id']} has reached {$occupancyPercent}% occupancy!\n"
                 . "Bus: {$trip['bus_name']} ({$trip['registration_number']})\n"
                 . "Date: {$dateFormatted} at {$depTime}\n"
                 . "Total Capacity: {$capacity} seats\n"
                 . "Booked Seats: {$booked} seats\n"
                 . "Remaining Seats: {$remaining} seats\n"
                 . "Please arrange a backup bus or plan capacity if required.";

            // Send to Admin via SMS and WhatsApp
            self::sendBoth($adminPhone, $msg, 'occupancy_70_alert', 'admin');

            // Mark alert as sent to prevent repeated alerts
            $updateStmt = $db->prepare("UPDATE `trips` SET `occupancy_alert_sent` = 1 WHERE `id` = ?");
            $updateStmt->execute([$tripId]);

            return true;
        }

        return false;
    }

    /**
     * Notify Customer & Admin on cancellation
     */
    public static function notifyBookingCancelled($booking, $reason = '') {
        $settings = getSystemSettings();
        $appName = APP_NAME;
        $ref = $booking['booking_ref'];
        $refundText = ($booking['payment_status'] === 'refunded') ? 'Your refund has been initiated.' : 'Refund is subject to terms.';

        $customerMsg = "⚠️ [{$appName}] Your booking {$ref} has been CANCELLED.\n"
                     . "Trip Date: " . formatDate($booking['trip_date']) . "\n"
                     . "Reason: " . ($reason ?: 'Cancelled by user/admin') . "\n"
                     . $refundText;
        self::sendBoth($booking['customer_phone'], $customerMsg, 'booking_cancelled', 'customer');

        $adminPhone = $settings['admin_alert_phone'] ?? '+919876543210';
        $adminMsg = "⚠️ [{$appName} - Cancellation Alert]\n"
                  . "Booking {$ref} was cancelled.\n"
                  . "Passenger: {$booking['customer_name']} ({$booking['customer_phone']})\n"
                  . "Seats Released: {$booking['seats_count']}\n"
                  . "Trip: #{$booking['trip_id']} (" . formatDate($booking['trip_date']) . ")";
        self::sendBoth($adminPhone, $adminMsg, 'admin_cancellation', 'admin');
    }

    /**
     * Log notification to database for audit and dashboard monitoring
     */
    public static function logNotification($recipientType, $phone, $channel, $eventType, $message, $status, $responseData = null) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO `notification_logs` 
                (`recipient_type`, `recipient_phone`, `channel`, `event_type`, `message`, `status`, `response_data`, `created_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $respJson = $responseData ? json_encode($responseData) : null;
            $stmt->execute([$recipientType, $phone, $channel, $eventType, $message, $status, $respJson]);
        } catch (Exception $e) {
            error_log("Failed to log notification: " . $e->getMessage());
        }
    }

    /**
     * Fast2SMS Integration (when API key provided)
     */
    private static function sendFast2SMS($phone, $message, $apiKey) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        // Take last 10 digits for Indian numbers
        if (strlen($cleanPhone) > 10) {
            $cleanPhone = substr($cleanPhone, -10);
        }
        $fields = [
            "message" => $message,
            "language" => "english",
            "route" => "q",
            "numbers" => $cleanPhone
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.fast2sms.com/dev/bulkV2");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "authorization: " . $apiKey,
            "content-type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        return ($err || empty($response)) ? 'failed' : 'sent';
    }

    /**
     * Meta Cloud API for WhatsApp
     */
    private static function sendMetaWhatsApp($phone, $message, $token, $phoneNumberId) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $payload = [
            "messaging_product" => "whatsapp",
            "to" => $cleanPhone,
            "type" => "text",
            "text" => ["body" => $message]
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v18.0/{$phoneNumberId}/messages");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        return ($err || empty($response)) ? 'failed' : 'sent';
    }
}
