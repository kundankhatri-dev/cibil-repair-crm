<?php
// ============================================================
// CIBIL REPAIR CRM - Delete Registration Code API
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
// SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
// AUTHENTICATION
// ============================================================

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login first.']);
    exit;
}

$loggedInUserId = (int)$_SESSION['user_id'];
$loggedInRole = $_SESSION['user_role'] ?? '';

// ============================================================
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$codeId = isset($input['code_id']) ? intval($input['code_id']) : 0;

// ============================================================
// VALIDATION
// ============================================================

if ($codeId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid code ID']);
    exit;
}

// ============================================================
# CHECK CODE EXISTS
// ============================================================

$checkStmt = mysqli_prepare($conn, "SELECT id, is_used, created_by, code, role FROM registration_codes WHERE id = ?");
mysqli_stmt_bind_param($checkStmt, 'i', $codeId);
mysqli_stmt_execute($checkStmt);
$result = mysqli_stmt_get_result($checkStmt);
$code = mysqli_fetch_assoc($result);
mysqli_stmt_close($checkStmt);

if (!$code) {
    echo json_encode(['success' => false, 'error' => 'Code not found']);
    exit;
}

// ============================================================
# CHECK PERMISSIONS
// ============================================================

// Admin can delete any code
if ($loggedInRole === 'admin') {
    // Admin has full access
} else {
    // Partners/others can only delete codes they created
    if ($code['created_by'] != $loggedInUserId) {
        echo json_encode(['success' => false, 'error' => 'You do not have permission to delete this code']);
        exit;
    }
}

// ============================================================
# PREVENT DELETING USED CODES
// ============================================================

if ($code['is_used'] == 1) {
    echo json_encode(['success' => false, 'error' => 'Cannot delete a code that has already been used']);
    exit;
}

// ============================================================
# DELETE CODE
// ============================================================

$stmt = mysqli_prepare($conn, "DELETE FROM registration_codes WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $codeId);

if (mysqli_stmt_execute($stmt)) {
    // Log activity
    $user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logDetails = "Deleted registration code: {$code['code']} (ID: $codeId)";
    
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
                         VALUES ($loggedInUserId, '$user_name', 'Deleted registration code', '$logDetails', '$ip', NOW())");
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration code deleted successfully',
        'data' => [
            'code_id' => $codeId,
            'code' => $code['code'],
            'role' => $code['role']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete code: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit;
?>