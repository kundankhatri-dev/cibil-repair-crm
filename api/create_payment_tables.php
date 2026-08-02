<?php
// ============================================================
// CIBIL REPAIR CRM - Create Payment Tables
// Run this once to create the necessary tables
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADER =====
header('Content-Type: application/json');

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

if (!in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$results = [];

// ============================================================
// CREATE PAYMENTS TABLE
// ============================================================

$payments_sql = "CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
    `order_id` VARCHAR(100) DEFAULT NULL,
    `service_id` INT UNSIGNED DEFAULT NULL,
    `service_name` VARCHAR(100) DEFAULT NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_email` VARCHAR(150) NOT NULL,
    `customer_phone` VARCHAR(15) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'razorpay',
    `payment_status` ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'completed',
    `payment_date` DATE NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `card_last4` VARCHAR(4) DEFAULT NULL,
    `card_bank` VARCHAR(100) DEFAULT NULL,
    `card_type` VARCHAR(50) DEFAULT NULL,
    `upi_id` VARCHAR(100) DEFAULT NULL,
    `reference_id` VARCHAR(100) DEFAULT NULL,
    `customer_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_transaction_id` (`transaction_id`),
    INDEX `idx_payment_status` (`payment_status`),
    INDEX `idx_payment_date` (`payment_date`),
    INDEX `idx_customer_email` (`customer_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $payments_sql)) {
    $results['payments'] = 'Created successfully';
} else {
    $results['payments'] = 'Error: ' . mysqli_error($conn);
}

// ============================================================
// CREATE CASES TABLE
// ============================================================

$cases_sql = "CREATE TABLE IF NOT EXISTS `cases` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_no` VARCHAR(20) NOT NULL UNIQUE,
    `payment_id` INT UNSIGNED NOT NULL,
    `service_name` VARCHAR(100) DEFAULT NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `client_name` VARCHAR(100) NOT NULL,
    `client_email` VARCHAR(150) DEFAULT NULL,
    `client_phone` VARCHAR(15) DEFAULT NULL,
    `status` ENUM('pending','active','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    `assigned_to` INT UNSIGNED DEFAULT NULL,
    `priority` ENUM('low','medium','high','urgent') DEFAULT 'medium',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_case_no` (`case_no`),
    INDEX `idx_case_status` (`status`),
    INDEX `idx_case_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $cases_sql)) {
    $results['cases'] = 'Created successfully';
} else {
    $results['cases'] = 'Error: ' . mysqli_error($conn);
}

// ============================================================
// CREATE CASE LOGS TABLE
// ============================================================

$case_logs_sql = "CREATE TABLE IF NOT EXISTS `case_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `user_name` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_case_id` (`case_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (mysqli_query($conn, $case_logs_sql)) {
    $results['case_logs'] = 'Created successfully';
} else {
    $results['case_logs'] = 'Error: ' . mysqli_error($conn);
}

// ============================================================
// RESPONSE
// ============================================================

$allSuccess = true;
foreach ($results as $key => $value) {
    if (strpos($value, 'Error') !== false) {
        $allSuccess = false;
        break;
    }
}

echo json_encode([
    'success' => $allSuccess,
    'message' => $allSuccess ? 'All tables created successfully' : 'Some tables failed to create',
    'results' => $results
]);

mysqli_close($conn);
exit;
?>