<?php
// ============================================================
// CIBIL REPAIR CRM - Delete Expense API (with GST)
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== START OUTPUT BUFFERING =====
ob_start();

// ===== SET HEADERS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== REMOVED: Direct access check =====
// if (basename($_SERVER['PHP_SELF']) === 'delete_expense.php') {
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

if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid request method. Use DELETE or POST']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$id = isset($input['id']) ? intval($input['id']) : 0;
$force = isset($input['force']) ? filter_var($input['force'], FILTER_VALIDATE_BOOLEAN) : false;

if (!$id && isset($_GET['id'])) {
    $id = intval($_GET['id']);
}

if (!$id) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Expense ID is required']);
    exit();
}

// ============================================================
# CHECK IF EXPENSE EXISTS
// ============================================================

$result = mysqli_query($conn, "SELECT * FROM expenses WHERE id = $id");
if (!$result || mysqli_num_rows($result) == 0) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Expense not found']);
    exit();
}
$expense = mysqli_fetch_assoc($result);

// ============================================================
# CHECK FOR DEPENDENCIES
// ============================================================

$related = [];

// Check if expense is already paid (has transactions)
if ($expense['status'] === 'paid') {
    $txDesc = "Expense Payment: {$expense['category']}";
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM transactions WHERE description LIKE '%$txDesc%' AND type = 'debit'");
    $count = mysqli_fetch_assoc($result)['count'] ?? 0;
    if ($count > 0) {
        $related[] = ['type' => 'transactions', 'count' => $count, 'message' => "$count transaction(s)"];
    }
}

// ============================================================
# IF RELATED RECORDS EXIST AND FORCE IS FALSE
// ============================================================

if (!empty($related) && !$force) {
    $relatedMessages = array_column($related, 'message');
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Cannot delete expense. Has related records: ' . implode(', ', $relatedMessages) . '. Use force=true to delete anyway.',
        'data' => [
            'expense' => [
                'id' => $id,
                'category' => $expense['category'],
                'amount' => (float)$expense['amount'],
                'status' => $expense['status']
            ],
            'related' => $related
        ]
    ]);
    exit();
}

// ============================================================
# DELETE EXPENSE
// ============================================================

$sql = "DELETE FROM expenses WHERE id = $id";
$result = mysqli_query($conn, $sql);

if ($result) {
    // Log activity
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logDetails = "Deleted expense: {$expense['category']} (ID: $id, Amount: ₹{$expense['amount']})";
    
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
                         VALUES ($user_id, '$user_name', 'Deleted expense', '$logDetails', '$ip', NOW())");
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Expense deleted successfully',
        'data' => [
            'id' => $id,
            'category' => $expense['category'],
            'description' => $expense['description'],
            'amount' => (float)$expense['amount'],
            'status' => $expense['status'],
            'deleted_at' => date('Y-m-d H:i:s'),
            'related_records' => $related,
            'force_deleted' => $force
        ]
    ]);
} else {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete expense: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
exit;
?>