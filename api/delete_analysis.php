<?php
// ============================================================
// CIBIL REPAIR CRM - Delete Document Analysis API
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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login first.']);
    exit;
}

$loggedInUserId = (int)$_SESSION['user_id'];
$loggedInRole = $_SESSION['user_role'] ?? '';

// Check if user has admin role
if (!in_array($loggedInRole, ['admin', 'super_admin', 'manager'])) {
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

$id = isset($input['id']) ? intval($input['id']) : 0;

// ============================================================
// VALIDATION
// ============================================================

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

// ============================================================
# CHECK IF DOCUMENT_ANALYSES TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'document_analyses'");
if (mysqli_num_rows($tableCheck) == 0) {
    // Create the table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS document_analyses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        document_type VARCHAR(100) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        content TEXT,
        analysis_result TEXT,
        status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $createTable);
}

// ============================================================
# CHECK IF ANALYSIS EXISTS
// ============================================================

$selectSql = "SELECT id, user_id, document_type, filename, status, created_at 
              FROM document_analyses 
              WHERE id = ?";
$selectStmt = mysqli_prepare($conn, $selectSql);
mysqli_stmt_bind_param($selectStmt, 'i', $id);
mysqli_stmt_execute($selectStmt);
$selectResult = mysqli_stmt_get_result($selectStmt);
$analysis = mysqli_fetch_assoc($selectResult);
mysqli_stmt_close($selectStmt);

if (!$analysis) {
    echo json_encode(['success' => false, 'error' => 'Document analysis not found']);
    exit;
}

// ============================================================
# CHECK PERMISSIONS
// ============================================================

// Admin can delete any analysis, others can only delete their own
if (!in_array($loggedInRole, ['admin', 'super_admin'])) {
    if ($analysis['user_id'] != $loggedInUserId) {
        echo json_encode(['success' => false, 'error' => 'You do not have permission to delete this analysis']);
        exit;
    }
}

// ============================================================
# DELETE THE ANALYSIS
// ============================================================

$deleteSql = "DELETE FROM document_analyses WHERE id = ?";
$deleteStmt = mysqli_prepare($conn, $deleteSql);
mysqli_stmt_bind_param($deleteStmt, 'i', $id);

if (mysqli_stmt_execute($deleteStmt)) {
    // Log activity
    $user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logDetails = "Deleted document analysis: {$analysis['filename']} (ID: $id)";
    
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
                         VALUES ($loggedInUserId, '$user_name', 'Deleted document analysis', '$logDetails', '$ip', NOW())");
    
    echo json_encode([
        'success' => true,
        'message' => 'Document analysis deleted successfully',
        'data' => [
            'id' => $id,
            'filename' => $analysis['filename'],
            'document_type' => $analysis['document_type'],
            'status' => $analysis['status']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete document analysis: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($deleteStmt);
mysqli_close($conn);
exit;
?>