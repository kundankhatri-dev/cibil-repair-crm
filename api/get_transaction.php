<?php
// ============================================================
// CIBIL REPAIR CRM - Get Single Transaction API (with GST)
// Endpoint: /api/get_transaction.php
// Method: GET
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// ============================================================
// GST CONFIGURATION
// ============================================================

define('GST_RATE', 18);
define('GST_CGST', 9);
define('GST_SGST', 9);

// ============================================================
// CHECK IF TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'transactions'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Transactions table not found']);
    exit;
}

// ============================================================
# GET PARAMETERS
// ============================================================

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$include_gst = isset($_GET['include_gst']) ? filter_var($_GET['include_gst'], FILTER_VALIDATE_BOOLEAN) : true;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Transaction ID is required']);
    exit;
}

// ============================================================
# GET TRANSACTION
// ============================================================

$sql = "SELECT * FROM transactions WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transaction = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$transaction) {
    echo json_encode(['success' => false, 'error' => 'Transaction not found']);
    exit;
}

// ============================================================
# GET CUSTOMER DETAILS
// ============================================================

$customer = null;
if (!empty($transaction['customer_id'])) {
    $cSql = "SELECT id, name, email, phone, city, status FROM customers WHERE id = ?";
    $cStmt = mysqli_prepare($conn, $cSql);
    mysqli_stmt_bind_param($cStmt, 'i', $transaction['customer_id']);
    mysqli_stmt_execute($cStmt);
    $cResult = mysqli_stmt_get_result($cStmt);
    $customer = mysqli_fetch_assoc($cResult);
    mysqli_stmt_close($cStmt);
}

// ============================================================
# GET PARTNER DETAILS
// ============================================================

$partner = null;
if (!empty($transaction['partner_id'])) {
    $pSql = "SELECT id, name, email, phone, company_name, commission_rate FROM partners WHERE id = ?";
    $pStmt = mysqli_prepare($conn, $pSql);
    mysqli_stmt_bind_param($pStmt, 'i', $transaction['partner_id']);
    mysqli_stmt_execute($pStmt);
    $pResult = mysqli_stmt_get_result($pStmt);
    $partner = mysqli_fetch_assoc($pResult);
    mysqli_stmt_close($pStmt);
}

// ============================================================
# CALCULATE GST
// ============================================================

$amount = (float)($transaction['amount'] ?? 0);
$gstAmount = $amount * GST_RATE / 100;
$cgstAmount = $amount * GST_CGST / 100;
$sgstAmount = $amount * GST_SGST / 100;
$totalAmount = $amount + $gstAmount;

// ============================================================
# RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Transaction retrieved successfully',
    'data' => [
        'id' => intval($transaction['id']),
        'date' => $transaction['date'] ?? null,
        'description' => $transaction['description'] ?? '',
        'amount' => round($amount, 2),
        'type' => $transaction['type'] ?? 'credit',
        'method' => $transaction['method'] ?? 'Cash',
        'gst' => $include_gst ? [
            'gst_rate' => GST_RATE,
            'cgst_rate' => GST_CGST,
            'sgst_rate' => GST_SGST,
            'cgst_amount' => round($cgstAmount, 2),
            'sgst_amount' => round($sgstAmount, 2),
            'total_gst' => round($gstAmount, 2),
            'total_with_gst' => round($totalAmount, 2)
        ] : null,
        'total_with_gst' => $include_gst ? round($totalAmount, 2) : round($amount, 2),
        'reference_id' => $transaction['reference_id'] ?? '',
        'customer_id' => isset($transaction['customer_id']) ? intval($transaction['customer_id']) : null,
        'customer' => $customer,
        'partner_id' => isset($transaction['partner_id']) ? intval($transaction['partner_id']) : null,
        'partner' => $partner,
        'balance_after' => floatval($transaction['balance_after'] ?? 0),
        'created_at' => $transaction['created_at'] ?? null,
        'updated_at' => $transaction['updated_at'] ?? null,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>