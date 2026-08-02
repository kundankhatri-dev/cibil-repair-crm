<?php
// ============================================================
// PARTNER API CONFIGURATION
// Secure database connection and settings for Partner Dashboard
// ============================================================

// ========== ERROR REPORTING (Adjust for production) ==========
// Set to 0 in production, 1 in development
error_reporting(1);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// ========== CORS HEADERS ==========
// Handle preflight requests first
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit();
}

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');

// ========== DATABASE CONFIGURATION (Direct credentials) ==========
$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

// ========== CREATE DATABASE CONNECTION ==========
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    $error_response = [
        'success' => false, 
        'error' => 'Database connection failed. Please try again later.',
        'code' => 500
    ];
    error_log('Database connection error: ' . mysqli_connect_error());
    echo json_encode($error_response);
    exit;
}

// ========== CONNECTION SETTINGS ==========
// Set charset to UTF-8 for proper handling of special characters
mysqli_set_charset($conn, 'utf8mb4');

// Set session timezone
mysqli_query($conn, "SET time_zone = '+05:30'"); // IST

// ========== SESSION MANAGEMENT ==========
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters for cross-path compatibility
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Also try to get session from existing cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['PHPSESSID'])) {
    session_id($_COOKIE['PHPSESSID']);
    session_start();
}

// ========== TIMEZONE SETTING ==========
date_default_timezone_set('Asia/Kolkata');

// ========== CREATE REQUIRED DIRECTORIES ==========
$directories = [
    __DIR__ . '/logs',
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/partner_documents',
    __DIR__ . '/../uploads/tickets'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ========== CREATE REQUIRED TABLES IF NOT EXISTS ==========

// Partner notifications table
$notificationsTable = 'partner_notifications';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$notificationsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS $notificationsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        read_at DATETIME DEFAULT NULL,
        link VARCHAR(500) DEFAULT NULL,
        icon VARCHAR(50) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $createTable);
}

// Partner leads table check - ensure required columns exist
$result = mysqli_query($conn, "SHOW COLUMNS FROM leads");
if ($result) {
    $existing_columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $existing_columns[] = $row['Field'];
    }
    
    // Add missing columns if needed
    if (!in_array('partner_id', $existing_columns)) {
        mysqli_query($conn, "ALTER TABLE leads ADD COLUMN partner_id INT DEFAULT NULL");
    }
    if (!in_array('service_type', $existing_columns)) {
        mysqli_query($conn, "ALTER TABLE leads ADD COLUMN service_type VARCHAR(100) DEFAULT NULL");
    }
    if (!in_array('source', $existing_columns)) {
        mysqli_query($conn, "ALTER TABLE leads ADD COLUMN source VARCHAR(100) DEFAULT 'Website'");
    }
    if (!in_array('notes', $existing_columns)) {
        mysqli_query($conn, "ALTER TABLE leads ADD COLUMN notes TEXT DEFAULT NULL");
    }
}

// Bank details table
$bankTable = 'bank_details';
$checkBankTable = mysqli_query($conn, "SHOW TABLES LIKE '$bankTable'");
if (mysqli_num_rows($checkBankTable) == 0) {
    $createBankTable = "CREATE TABLE IF NOT EXISTS $bankTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        bank_name VARCHAR(255) DEFAULT NULL,
        account_number VARCHAR(100) DEFAULT NULL,
        ifsc_code VARCHAR(50) DEFAULT NULL,
        account_holder VARCHAR(255) DEFAULT NULL,
        upi_id VARCHAR(100) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $createBankTable);
}

// Payout requests table
$payoutTable = 'payout_requests';
$checkPayoutTable = mysqli_query($conn, "SHOW TABLES LIKE '$payoutTable'");
if (mysqli_num_rows($checkPayoutTable) == 0) {
    $createPayoutTable = "CREATE TABLE IF NOT EXISTS $payoutTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        method VARCHAR(50) DEFAULT 'Bank Transfer',
        status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
        reference VARCHAR(100) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        processed_date DATETIME DEFAULT NULL,
        INDEX idx_partner_id (partner_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $createPayoutTable);
}

// Support tickets table
$ticketsTable = 'support_tickets';
$checkTicketsTable = mysqli_query($conn, "SHOW TABLES LIKE '$ticketsTable'");
if (mysqli_num_rows($checkTicketsTable) == 0) {
    $createTicketsTable = "CREATE TABLE IF NOT EXISTS $ticketsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_role VARCHAR(50) DEFAULT 'partner',
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
        admin_reply TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $createTicketsTable);
}

// ========== APPLICATION CONSTANTS ==========
define('APP_NAME', 'CIBIL Repair Partner Portal');
define('APP_VERSION', '1.0.0');
define('API_BASE_PATH', '/api/partner/');

// File upload settings
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_UPLOAD_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
define('ALLOWED_UPLOAD_MIMES', [
    'image/jpeg', 'image/jpg', 'image/png',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

// Pagination defaults
define('DEFAULT_PAGE_LIMIT', 20);
define('MAX_PAGE_LIMIT', 100);

// Payout settings
define('MIN_PAYOUT_AMOUNT', 1000);
define('MAX_PAYOUT_AMOUNT', 100000);
define('DEFAULT_COMMISSION_RATE', 30);

// Tier settings
define('TIER_BRONZE_MIN', 0);
define('TIER_BRONZE_COMMISSION', 30);
define('TIER_SILVER_MIN', 10);
define('TIER_SILVER_COMMISSION', 35);
define('TIER_GOLD_MIN', 25);
define('TIER_GOLD_COMMISSION', 40);
define('TIER_PLATINUM_MIN', 50);
define('TIER_PLATINUM_COMMISSION', 45);
define('TIER_DIAMOND_MIN', 100);
define('TIER_DIAMOND_COMMISSION', 50);

// ========== HELPER FUNCTIONS ==========

/**
 * Get database connection
 * @return mysqli
 */
function getConnection() {
    global $conn;
    return $conn;
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    if ($data === null) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Escape and sanitize for database
 * @param string $data
 * @return string
 */
function escapeString($data) {
    global $conn;
    return mysqli_real_escape_string($conn, sanitizeInput($data));
}

/**
 * Check if user is logged in as partner
 * @return bool
 */
function isPartnerLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['user_role']) && 
           $_SESSION['user_role'] === 'partner';
}

/**
 * Get current partner ID
 * @return int|null
 */
function getCurrentPartnerId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current partner name
 * @return string|null
 */
function getCurrentPartnerName() {
    return $_SESSION['user_name'] ?? null;
}

/**
 * Get partner tier based on conversions
 * @param int $conversions
 * @return array
 */
function getPartnerTier($conversions) {
    if ($conversions >= TIER_DIAMOND_MIN) {
        return ['name' => 'Diamond', 'commission' => TIER_DIAMOND_COMMISSION, 'icon' => '👑', 'level' => 5];
    } elseif ($conversions >= TIER_PLATINUM_MIN) {
        return ['name' => 'Platinum', 'commission' => TIER_PLATINUM_COMMISSION, 'icon' => '💎', 'level' => 4];
    } elseif ($conversions >= TIER_GOLD_MIN) {
        return ['name' => 'Gold', 'commission' => TIER_GOLD_COMMISSION, 'icon' => '🥇', 'level' => 3];
    } elseif ($conversions >= TIER_SILVER_MIN) {
        return ['name' => 'Silver', 'commission' => TIER_SILVER_COMMISSION, 'icon' => '🥈', 'level' => 2];
    } else {
        return ['name' => 'Bronze', 'commission' => TIER_BRONZE_COMMISSION, 'icon' => '🥉', 'level' => 1];
    }
}

/**
 * Send JSON response
 * @param bool $success
 * @param string $message
 * @param array $data
 * @param int $status_code
 */
function sendResponse($success, $message = '', $data = [], $status_code = 200) {
    http_response_code($status_code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

/**
 * Send error response
 * @param string $error
 * @param int $status_code
 */
function sendError($error, $status_code = 400) {
    sendResponse(false, $error, [], $status_code);
}

/**
 * Send success response
 * @param string $message
 * @param array $data
 */
function sendSuccess($message = '', $data = []) {
    sendResponse(true, $message, $data);
}

/**
 * Log error message
 * @param string $message
 * @param array $context
 */
function logError($message, $context = []) {
    $log_entry = date('Y-m-d H:i:s') . ' - ERROR - ' . $message;
    if (!empty($context)) {
        $log_entry .= ' - ' . json_encode($context);
    }
    error_log($log_entry . PHP_EOL, 3, __DIR__ . '/logs/error.log');
}

/**
 * Log info message
 * @param string $message
 * @param array $context
 */
function logInfo($message, $context = []) {
    $log_entry = date('Y-m-d H:i:s') . ' - INFO - ' . $message;
    if (!empty($context)) {
        $log_entry .= ' - ' . json_encode($context);
    }
    error_log($log_entry . PHP_EOL, 3, __DIR__ . '/logs/app.log');
}

/**
 * Format file size for display
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    if ($bytes === null || $bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (10 digits)
 * @param string $phone
 * @return bool
 */
function isValidPhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

/**
 * Validate IFSC code format
 * @param string $ifsc
 * @return bool
 */
function isValidIFSC($ifsc) {
    return preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc);
}

/**
 * Get client IP address
 * @return string
 */
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * Get relative time string
 * @param string $datetime
 * @return string
 */
function getTimeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('d M Y', $timestamp);
}

// ========== CREATE LOG DIRECTORY ==========
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// ========== INITIALIZE WELCOME NOTIFICATION FOR NEW PARTNERS ==========
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'partner') {
    $partner_id = $_SESSION['user_id'];
    
    // Check if welcome notification exists
    $check_welcome = mysqli_query($conn, "SELECT id FROM $notificationsTable WHERE partner_id = $partner_id AND title LIKE '%Welcome%'");
    if (mysqli_num_rows($check_welcome) == 0) {
        $welcome_title = 'Welcome to Partner Dashboard! 🎉';
        $welcome_msg = 'Thank you for joining CIBIL Repair as a partner. Start adding leads to earn up to 50% commission!';
        $insert = mysqli_query($conn, "INSERT INTO $notificationsTable (partner_id, title, message, type, created_at) VALUES ($partner_id, '$welcome_title', '$welcome_msg', 'success', NOW())");
    }
}

// ========== END OF CONFIG ==========
?>