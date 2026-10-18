<?php
/**
 * Core Helper Functions
 */

require_once __DIR__ . '/../config/config.php';

// Flash messages helpers
function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][$type] = $message;
}

function getFlash($type) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

function hasFlash($type) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['flash'][$type]);
}

// Generate unique booking reference (e.g., BK-261012-7890)
function generateBookingRef() {
    return 'BK-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

// Generate 6-digit OTP
function generateOTP($length = 6) {
    return str_pad(random_int(100000, 999999), $length, '0', STR_PAD_LEFT);
}

// Calculate Occupancy Percentage: (Booked Seats / Total Capacity) * 100
function calculateOccupancy($bookedSeats, $totalCapacity) {
    if ($totalCapacity <= 0) return 0;
    $percentage = ($bookedSeats / $totalCapacity) * 100;
    return round($percentage, 1);
}

// Format Currency
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 2);
}

// Format Date
function formatDate($dateStr, $format = 'D, d M Y') {
    if (empty($dateStr)) return '';
    return date($format, strtotime($dateStr));
}

// Format Time
function formatTime($timeStr, $format = 'h:i A') {
    if (empty($timeStr)) return '';
    return date($format, strtotime($timeStr));
}

// Clean Input
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

// JSON Output Helper
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Calculate remaining seats for a trip
function getTripSeatAvailability($tripId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT total_capacity, booked_seats FROM `trips` WHERE id = ?");
    $stmt->execute([$tripId]);
    $trip = $stmt->fetch();
    if (!$trip) return 0;
    return max(0, $trip['total_capacity'] - $trip['booked_seats']);
}
