<?php
/**
 * Smart Library Management System - Database & Core Helper Functions
 * Manages JSON file reading, writing, session state, and automated fine calculation.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DATA_DIR', __DIR__ . '/../data');

// Read data from a JSON file
function read_json($filename) {
    $filepath = DATA_DIR . '/' . $filename;
    if (!file_exists($filepath)) {
        return [];
    }
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

// Write data safely to a JSON file
function write_json($filename, $data) {
    $filepath = DATA_DIR . '/' . $filename;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($filepath, $json, LOCK_EX) !== false;
}

// Send JSON API Response
function json_response($status, $message, $data = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Get Currently Logged In User
function get_logged_in_user() {
    return $_SESSION['user'] ?? null;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user']);
}

// Check if current user is Admin
function is_admin() {
    return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
}

// Automated Overdue Fine Calculation Engine (₹5 per day overdue)
function calculate_overdue_fine($due_date_str, $return_date_str = null) {
    $dueDate = new DateTime($due_date_str);
    $checkDate = $return_date_str ? new DateTime($return_date_str) : new DateTime();

    // Reset time components for accurate day comparison
    $dueDate->setTime(0, 0, 0);
    $checkDate->setTime(0, 0, 0);

    if ($checkDate > $dueDate) {
        $interval = $dueDate->diff($checkDate);
        $overdueDays = (int)$interval->days;
        $fineRatePerDay = 5; // ₹5 per day
        return [
            'is_overdue' => true,
            'overdue_days' => $overdueDays,
            'fine_amount' => $overdueDays * $fineRatePerDay
        ];
    }

    return [
        'is_overdue' => false,
        'overdue_days' => 0,
        'fine_amount' => 0
    ];
}
?>
