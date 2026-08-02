<?php
// ============================================================
// CIBIL REPAIR CRM - Log Activity API
// ============================================================

// ============================================================
// CORS & HEADERS
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

// ============================================================
// CREATE TABLE IF NOT EXISTS
// ============================================================

$createTable = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    user_name VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_user_name (user_name),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

mysqli_query($conn, $createTable);

// ============================================================
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

// Get user info
$userName = isset($input['user_name']) ? trim($input['user_name']) : ($_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System');
$action = isset($input['action']) ? trim($input['action']) : 'Unknown action';
$details = isset($input['details']) ? trim($input['details']) : '';
$userId = isset($input['user_id']) ? intval($input['user_id']) : ($_SESSION['user_id'] ?? null);

// Get IP and User Agent
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// ============================================================
# VALIDATE
// ============================================================

if (empty($action)) {
    echo json_encode(['success' => false, 'error' => 'Action is required']);
    exit;
}

if (empty($userName)) {
    $userName = 'System';
}

// ============================================================
# INSERT ACTIVITY LOG
// ============================================================

$sql = "INSERT INTO activity_logs (user_id, user_name, action, details, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'isssss', $userId, $userName, $action, $details, $ip, $userAgent);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    if ($affected > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Activity logged successfully',
            'data' => [
                'id' => mysqli_insert_id($conn),
                'user_name' => $userName,
                'action' => $action,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to log activity']);
    }
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to log activity: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
exit;
?>