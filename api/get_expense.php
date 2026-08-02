<?php
// ============================================================
// CIBIL REPAIR CRM - Get Single Expense API (with GST)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GST CONFIGURATION
// ============================================================

define('GST_RATE', 18);
define('GST_CGST', 9);
define('GST_SGST', 9);

// ============================================================
// GET PARAMETERS
// ============================================================

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Expense ID is required']);
    mysqli_close($conn);
    exit;
}

// ============================================================
// GET EXPENSE
// ============================================================

$result = mysqli_query($conn, "SELECT * FROM expenses WHERE id = $id");
$expense = mysqli_fetch_assoc($result);

if (!$expense) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Expense not found']);
    mysqli_close($conn);
    exit;
}

// ============================================================
// GET RELATED DATA
// ============================================================

// Vendor/Partner
$vendor = null;
if (!empty($expense['vendor_id'])) {
    $r = mysqli_query($conn, "SELECT id, name, email, phone, company_name, status FROM partners WHERE id = " . (int)$expense['vendor_id']);
    $vendor = mysqli_fetch_assoc($r);
}

// Approver
$approver = null;
if (!empty($expense['approved_by'])) {
    $r = mysqli_query($conn, "SELECT id, name, email, role FROM users WHERE id = " . (int)$expense['approved_by']);
    $approver = mysqli_fetch_assoc($r);
}

// Related Transactions
$transactions = [];
$r = mysqli_query($conn, "SELECT id, date, description, amount, type, method FROM transactions WHERE description LIKE '%Expense: " . mysqli_real_escape_string($conn, $expense['category']) . "%' ORDER BY date DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($r)) { $transactions[] = $row; }

// Activities
$activities = [];
$r = mysqli_query($conn, "SELECT user_name, action, details, created_at FROM activity_logs WHERE details LIKE '%Expense ID: $id%' ORDER BY created_at DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($r)) { $activities[] = $row; }

// ============================================================
// GST CALCULATION
// ============================================================

$amount = (float)$expense['amount'];
$isGstApplicable = isset($expense['is_gst_applicable']) ? (bool)$expense['is_gst_applicable'] : false;
$gstRate = isset($expense['gst_rate']) ? (float)$expense['gst_rate'] : GST_RATE;
$gstAmount = isset($expense['gst_amount']) ? (float)$expense['gst_amount'] : ($isGstApplicable ? round($amount * $gstRate / 100, 2) : 0);
$cgstAmount = isset($expense['cgst_amount']) ? (float)$expense['cgst_amount'] : ($isGstApplicable ? round($gstAmount / 2, 2) : 0);
$sgstAmount = isset($expense['sgst_amount']) ? (float)$expense['sgst_amount'] : ($isGstApplicable ? round($gstAmount / 2, 2) : 0);
$totalWithGst = $amount + $gstAmount;

// ============================================================
// STATISTICS
// ============================================================

// Same category total
$r = mysqli_query($conn, "SELECT IFNULL(SUM(amount), 0) as total FROM expenses WHERE category = '" . mysqli_real_escape_string($conn, $expense['category']) . "'");
$categoryTotal = mysqli_fetch_assoc($r);
$categoryTotalAmount = $categoryTotal ? (float)$categoryTotal['total'] : 0;

// Same category count
$r = mysqli_query($conn, "SELECT COUNT(*) as count FROM expenses WHERE category = '" . mysqli_real_escape_string($conn, $expense['category']) . "'");
$categoryCount = mysqli_fetch_assoc($r);
$categoryCountNumber = $categoryCount ? (int)$categoryCount['count'] : 0;

// Expense age
$expenseAge = 0;
if (!empty($expense['created_at'])) {
    $created = new DateTime($expense['created_at']);
    $now = new DateTime();
    $expenseAge = $created->diff($now)->days;
}

// ============================================================
// STATUS TEXT
// ============================================================

$statusText = [
    'pending' => 'Pending Approval',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'paid' => 'Paid'
];

// ============================================================
// FORMAT RESPONSE
// ============================================================

$formattedExpense = [
    'id' => (int)$expense['id'],
    'category' => $expense['category'],
    'description' => $expense['description'] ?? '',
    'amount' => $amount,
    'date' => $expense['date'],
    'expense_type' => $expense['expense_type'] ?? 'operational',
    'payment_method' => $expense['payment_method'] ?? 'cash',
    'vendor_name' => $expense['vendor_name'] ?? '',
    'vendor_id' => $expense['vendor_id'] ? (int)$expense['vendor_id'] : null,
    'vendor' => $vendor,
    'receipt_no' => $expense['receipt_no'] ?? '',
    'receipt_url' => $expense['receipt_url'] ?? '',
    'notes' => $expense['notes'] ?? '',
    'approved_by' => $expense['approved_by'] ? (int)$expense['approved_by'] : null,
    'approver' => $approver,
    'status' => $expense['status'] ?? 'pending',
    'status_text' => $statusText[$expense['status'] ?? 'pending'] ?? 'Pending',
    'gst_details' => [
        'is_applicable' => $isGstApplicable,
        'gst_rate' => $gstRate,
        'gst_amount' => $gstAmount,
        'cgst_amount' => $cgstAmount,
        'sgst_amount' => $sgstAmount,
        'total_with_gst' => $totalWithGst
    ],
    'is_gst_applicable' => $isGstApplicable,
    'created_at' => $expense['created_at'],
    'stats' => [
        'expense_age' => $expenseAge . ' days',
        'same_category_total' => $categoryTotalAmount,
        'same_category_count' => $categoryCountNumber,
        'gst_saved' => $isGstApplicable ? $gstAmount : 0
    ],
    'transactions' => $transactions,
    'recent_activities' => $activities
];

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Expense retrieved successfully',
    'data' => $formattedExpense
]);

mysqli_close($conn);
exit;
?>