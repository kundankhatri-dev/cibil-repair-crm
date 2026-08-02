<?php
// ============================================================
// CIBIL REPAIR CRM - Delete User API
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== START OUTPUT BUFFERING =====
ob_start();

// ===== SET HEADERS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== REMOVED: Direct access check =====
// if (basename($_SERVER['PHP_SELF']) === 'delete_user.php') {
//     http_response_code(403);
//     exit('Direct access forbidden.');
// }

// ===== HANDLE PREFLIGHT =====
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
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';

if (!$user_id) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

if (!in_array($role, ['admin', 'super_admin'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required']);
    exit();
}

// ============================================================
// GET INPUT DATA
// ============================================================

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid request method. Use POST or DELETE']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$id = isset($input['id']) ? intval($input['id']) : 0;
$force = isset($input['force']) ? filter_var($input['force'], FILTER_VALIDATE_BOOLEAN) : false;

if (!$id) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit();
}

// ============================================================
# CHECK IF USER EXISTS
// ============================================================

$result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
if (!$result || mysqli_num_rows($result) == 0) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}
$user = mysqli_fetch_assoc($result);

// ============================================================
# PREVENT SELF-DELETION
// ============================================================

if ($id == $user_id) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'You cannot delete your own account']);
    exit();
}

// ============================================================
# PREVENT DELETING SUPER_ADMIN
// ============================================================

if ($user['role'] === 'super_admin') {
    $saResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'super_admin' AND status != 'deleted'");
    $saCount = mysqli_fetch_assoc($saResult);
    if ($saCount['count'] <= 1) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Cannot delete the only super administrator']);
        exit();
    }
}

// ============================================================
# CHECK DEPENDENCIES
// ============================================================

$related = [];

// Check if user has sales
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM sales WHERE customer_name = '{$user['name']}' OR customer_email = '{$user['email']}'");
$count = mysqli_fetch_assoc($result)['count'] ?? 0;
if ($count > 0) {
    $related[] = ['type' => 'sales', 'count' => $count, 'message' => "$count sale(s)"];
}

// Check if user has leads
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads WHERE email = '{$user['email']}'");
$count = mysqli_fetch_assoc($result)['count'] ?? 0;
if ($count > 0) {
    $related[] = ['type' => 'leads', 'count' => $count, 'message' => "$count lead(s)"];
}

// Check if user has quotations
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM quotations WHERE customer_email = '{$user['email']}'");
$count = mysqli_fetch_assoc($result)['count'] ?? 0;
if ($count > 0) {
    $related[] = ['type' => 'quotations', 'count' => $count, 'message' => "$count quotation(s)"];
}

// ============================================================
# IF RELATED RECORDS EXIST AND FORCE IS FALSE
// ============================================================

if (!empty($related) && !$force) {
    $relatedMessages = array_column($related, 'message');
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Cannot delete user. Has related records: ' . implode(', ', $relatedMessages) . '. Use force=true to delete anyway.',
        'data' => [
            'user' => [
                'id' => $id,
                'name' => $user['name'],
                'role' => $user['role']
            ],
            'related' => $related
        ]
    ]);
    exit();
}

// ============================================================
# DELETE USER
// ============================================================

$sql = "DELETE FROM users WHERE id = $id";
$result = mysqli_query($conn, $sql);

if ($result) {
    // Log activity
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logDetails = "Deleted user: {$user['name']} (ID: $id, Role: {$user['role']})";
    
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
                         VALUES ($user_id, '$user_name', 'Deleted user', '$logDetails', '$ip', NOW())");
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'User deleted successfully',
        'data' => [
            'id' => $id,
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'deleted_at' => date('Y-m-d H:i:s'),
            'related_records' => $related,
            'force_deleted' => $force
        ]
    ]);
} else {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete user: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
exit;
?>