<?php
// ============================================================
// CIBIL REPAIR CRM - Change User Status API (FULLY FIXED)
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

// ============================================================
// SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';

// Check authentication
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Check admin role
if (!in_array($role, ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required']);
    exit;
}

// ============================================================
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$id = isset($input['id']) ? intval($input['id']) : 0;
$status = isset($input['status']) ? trim($input['status']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';

// ============================================================
// VALIDATE INPUT
// ============================================================

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit;
}

if (!$status) {
    echo json_encode(['success' => false, 'error' => 'Status is required']);
    exit;
}

$allowedStatuses = ['pending', 'approved', 'active', 'inactive', 'suspended', 'deleted'];
if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

// ============================================================
# CHECK IF USER EXISTS
// ============================================================

$result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}
$existingUser = mysqli_fetch_assoc($result);

// Check if user is already in this status
if ($existingUser['status'] === $status) {
    echo json_encode(['success' => false, 'error' => 'User is already in ' . $status . ' status']);
    exit;
}

// Prevent self-status change
if ($id == $user_id && in_array($status, ['inactive', 'suspended', 'deleted'])) {
    echo json_encode(['success' => false, 'error' => 'You cannot change your own status']);
    exit;
}

// ============================================================
# UPDATE USER STATUS
// ============================================================

$oldStatus = $existingUser['status'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Update status using prepared statement
$updateSql = "UPDATE users SET status = ? WHERE id = ?";
$updateStmt = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($updateStmt, 'si', $status, $id);
$updateResult = mysqli_stmt_execute($updateStmt);
mysqli_stmt_close($updateStmt);

if ($updateResult) {
    // Get updated user
    $result2 = mysqli_query($conn, "SELECT id, name, email, phone, role, status FROM users WHERE id = $id");
    $updatedUser = mysqli_fetch_assoc($result2);
    
    // Log activity using prepared statement
    $logDetails = "User ID: $id, Name: {$existingUser['name']}, Status changed from '$oldStatus' to '$status'";
    if ($reason) {
        $logDetails .= ". Reason: $reason";
    }
    
    // Create activity_logs table if not exists
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_name VARCHAR(100),
        action VARCHAR(100),
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Use prepared statement for activity log
    $logSql = "INSERT INTO activity_logs (user_id, user_name, action, details, ip_address, created_at) 
               VALUES (?, ?, ?, ?, ?, NOW())";
    $logStmt = mysqli_prepare($conn, $logSql);
    mysqli_stmt_bind_param($logStmt, 'issss', $user_id, $user_name, 'User status changed', $logDetails, $ip);
    mysqli_stmt_execute($logStmt);
    mysqli_stmt_close($logStmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'User status updated successfully',
        'data' => [
            'user' => $updatedUser,
            'old_status' => $oldStatus,
            'new_status' => $status
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update user status'
    ]);
}

mysqli_close($conn);
exit;
?>