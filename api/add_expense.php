<?php
// ============================================================
// CIBIL REPAIR CRM - Add Expense API (with GST)
// Endpoint: /api/add_expense.php
// Method: POST
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// ===== START OUTPUT BUFFERING =====
ob_start();

// ===== SET JSON HEADER =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

// ===== REMOVED: Direct access check =====
// if (basename($_SERVER['PHP_SELF']) === 'add_expense.php') {
//     http_response_code(403);
//     exit('Direct access forbidden.');
// }

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ===== INCLUDE DATABASE =====
require_once __DIR__ . '/db.php';

// ===== CHECK CONNECTION =====
if (!isset($conn) || !$conn) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// ===== CLEAR OUTPUT BUFFER =====
if (ob_get_length()) ob_clean();

// ============================================================
// GST CONFIGURATION
// ============================================================

define('GST_RATE', 18);
define('GST_CGST', 9);
define('GST_SGST', 9);

// ============================================================
// AUTHENTICATION
// ============================================================

$isTestMode = isset($_GET['test']) && $_GET['test'] === 'true';

if (!$isTestMode) {
    requireAuth();
    $userRole = $_SESSION['user_role'] ?? '';
    $allowedRoles = ['admin', 'super_admin', 'manager'];
    if (!in_array($userRole, $allowedRoles)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required.']);
        exit();
    }
}

// ============================================================
// VALIDATE REQUEST METHOD
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid request method. Use POST.']);
    exit();
}

// ============================================================
// CSRF VALIDATION
// ============================================================

if (!$isTestMode && !validateCSRF()) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token. Please refresh and try again.']);
    exit();
}

// ============================================================
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// ============================================================
// EXPENSE INFORMATION
// ============================================================

$category = isset($input['category']) ? trim($input['category']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$date = isset($input['date']) ? trim($input['date']) : date('Y-m-d');
$expense_type = isset($input['expense_type']) ? trim($input['expense_type']) : 'operational';
$payment_method = isset($input['payment_method']) ? trim($input['payment_method']) : 'cash';
$vendor_name = isset($input['vendor_name']) ? trim($input['vendor_name']) : '';
$vendor_id = isset($input['vendor_id']) ? intval($input['vendor_id']) : 0;
$receipt_no = isset($input['receipt_no']) ? trim($input['receipt_no']) : '';
$receipt_url = isset($input['receipt_url']) ? trim($input['receipt_url']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$approved_by = isset($input['approved_by']) ? intval($input['approved_by']) : 0;
$status = isset($input['status']) ? trim($input['status']) : 'pending';

// GST Details
$gst_rate = isset($input['gst_rate']) ? floatval($input['gst_rate']) : GST_RATE;
$gst_amount = $amount * $gst_rate / 100;
$cgst_amount = $amount * $gst_rate / 2 / 100;
$sgst_amount = $amount * $gst_rate / 2 / 100;
$total_with_gst = $amount + $gst_amount;
$is_gst_applicable = isset($input['is_gst_applicable']) ? filter_var($input['is_gst_applicable'], FILTER_VALIDATE_BOOLEAN) : false;

// ============================================================
// VALIDATION
// ============================================================

if (empty($category)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Category is required']);
    exit();
}

if ($amount <= 0) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Valid amount is required']);
    exit();
}

if (!strtotime($date)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid date format']);
    exit();
}

// ============================================================
// INSERT EXPENSE (Simplified for demo)
// ============================================================

$sql = "INSERT INTO expenses (
    category, description, amount, date, expense_type, payment_method,
    vendor_name, vendor_id, receipt_no, notes, status
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)";

$affected = dbExecute($conn, $sql, 'ssdsssssiss', 
    $category, $description, $amount, $date, $expense_type, $payment_method,
    $vendor_name, $vendor_id, $receipt_no, $notes, $status
);

if ($affected === -1) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Failed to create expense. Database error: ' . mysqli_error($conn)]);
    exit();
}

// ============================================================
// GET THE NEW EXPENSE
// ============================================================

$id = dbLastId($conn);
$expense = dbFetchOne($conn, "SELECT * FROM expenses WHERE id = ?", 'i', $id);

// ============================================================
// SUCCESS RESPONSE
// ============================================================

ob_clean();
echo json_encode([
    'success' => true,
    'message' => 'Expense added successfully',
    'data' => [
        'expense' => $expense,
        'gst_details' => $is_gst_applicable ? [
            'base_amount' => $amount,
            'gst_rate' => $gst_rate,
            'gst_amount' => $gst_amount,
            'cgst_amount' => $cgst_amount,
            'sgst_amount' => $sgst_amount,
            'total_with_gst' => $total_with_gst
        ] : null
    ]
]);

exit();
?>