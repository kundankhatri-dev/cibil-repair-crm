<?php
// ============================================================
// CIBIL REPAIR CRM - Delete Backup API
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADERS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? '';

if (!$user_id || !in_array($role, ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required.']);
    exit;
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
// GET INPUT DATA
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST method allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$filename = isset($input['filename']) ? trim($input['filename']) : '';

// ============================================================
// VALIDATION
// ============================================================

if (empty($filename)) {
    echo json_encode(['success' => false, 'error' => 'Filename is required']);
    exit;
}

// Security: prevent directory traversal
$filename = basename($filename);

// Only allow .sql and .sql.gz files
$allowedExtensions = ['sql', 'sql.gz', 'gz'];
$ext = pathinfo($filename, PATHINFO_EXTENSION);
if (!in_array($ext, $allowedExtensions) && !str_ends_with($filename, '.sql.gz')) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit;
}

// ============================================================
# CHECK IF BACKUP DIRECTORY EXISTS
// ============================================================

$backupDir = __DIR__ . '/../backups/';
if (!file_exists($backupDir)) {
    echo json_encode(['success' => false, 'error' => 'Backup directory not found']);
    exit;
}

$filepath = $backupDir . $filename;

// ============================================================
# CHECK IF FILE EXISTS
// ============================================================

if (!file_exists($filepath) || !is_file($filepath)) {
    echo json_encode(['success' => false, 'error' => 'Backup file not found']);
    exit;
}

// ============================================================
# DELETE THE FILE
// ============================================================

if (unlink($filepath)) {
    // Log activity
    $user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logDetails = "Deleted backup file: $filename";
    
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_name VARCHAR(100),
        action VARCHAR(100),
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    mysqli_query($conn, "INSERT INTO activity_logs (user_id, user_name, action, details, ip_address, created_at) 
                         VALUES ($user_id, '$user_name', 'Deleted backup', '$logDetails', '$ip', NOW())");
    
    echo json_encode([
        'success' => true,
        'message' => 'Backup deleted successfully',
        'data' => [
            'filename' => $filename
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete backup file'
    ]);
}

mysqli_close($conn);
exit;
?>