<?php
// ============================================================
// CIBIL REPAIR CRM - Customer DELETE API (COMPLETE)
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
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';

if (!$user_id) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

if (!in_array($role, ['admin', 'super_admin', 'manager'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required']);
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
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$id = isset($input['id']) ? intval($input['id']) : 0;
$force = isset($input['force']) ? filter_var($input['force'], FILTER_VALIDATE_BOOLEAN) : false;

if ($id <= 0) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
    exit();
}

// ============================================================
// CHECK IF CUSTOMER EXISTS
// ============================================================

$result = mysqli_query($conn, "SELECT * FROM customers WHERE id = $id");
if (!$result || mysqli_num_rows($result) == 0) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Customer not found']);
    exit();
}
$customer = mysqli_fetch_assoc($result);

// ============================================================
# CHECK FOR DEPENDENCIES
// ============================================================

$related = [];

// Check if customer has sales
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM sales WHERE customer_email = '{$customer['email']}'");
$count = mysqli_fetch_assoc($result)['count'] ?? 0;
if ($count > 0) {
    $related[] = ['type' => 'sales', 'count' => $count, 'message' => "$count sale(s)"];
}

// Check if customer has leads
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads WHERE email = '{$customer['email']}'");
$count = mysqli_fetch_assoc($result)['count'] ?? 0;
if ($count > 0) {
    $related[] = ['type' => 'leads', 'count' => $count, 'message' => "$count lead(s)"];
}

// Check if customer has quotations
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM quotations WHERE customer_email = '{$customer['email']}'");
$count = mysqli_fetch_assoc($result)['count'] ?? 0;
if ($count > 0) {
    $related[] = ['type' => 'quotations', 'count' => $count, 'message' => "$count quotation(s)"];
}

// Check if customer has requests
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM customer_requests WHERE email = '{$customer['email']}'");
$count = mysqli_fetch_assoc($result)['count'] ?? 0;
if ($count > 0) {
    $related[] = ['type' => 'requests', 'count' => $count, 'message' => "$count request(s)"];
}

// Check if customer has payments
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM client_payments WHERE client_email = '{$customer['email']}'");
$count = mysqli_fetch_assoc($result)['count'] ?? 0;
if ($count > 0) {
    $related[] = ['type' => 'payments', 'count' => $count, 'message' => "$count payment(s)"];
}

// ============================================================
# IF RELATED RECORDS EXIST AND FORCE IS FALSE
// ============================================================

if (!empty($related) && !$force) {
    $relatedMessages = array_column($related, 'message');
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Cannot delete customer. Has related records: ' . implode(', ', $relatedMessages) . '. Use force=true to delete anyway.',
        'data' => [
            'customer' => [
                'id' => $id,
                'name' => $customer['name'],
                'email' => $customer['email']
            ],
            'related' => $related
        ]
    ]);
    exit();
}

// ============================================================
// DELETE CUSTOMER
// ============================================================

$sql = "DELETE FROM customers WHERE id = $id";
$result = mysqli_query($conn, $sql);

if ($result) {
    // Log activity
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logDetails = "Deleted customer: {$customer['name']} (ID: $id, Email: {$customer['email']})";
    
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
                         VALUES ($user_id, '$user_name', 'Deleted customer', '$logDetails', '$ip', NOW())");
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Customer deleted successfully',
        'data' => [
            'id' => $id,
            'name' => $customer['name'],
            'email' => $customer['email'],
            'deleted_at' => date('Y-m-d H:i:s'),
            'related_records' => $related,
            'force_deleted' => $force
        ]
    ]);
} else {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete customer: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
exit;
?>