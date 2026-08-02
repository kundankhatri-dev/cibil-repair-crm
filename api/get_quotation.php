<?php
// ============================================================
// CIBIL REPAIR CRM - Get Single Quotation API (with GST)
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

// Database connection
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

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// GST Configuration
define('GST_RATE', 18);
define('GST_CGST', 9);
define('GST_SGST', 9);

// Get parameters
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$include_gst = isset($_GET['include_gst']) ? filter_var($_GET['include_gst'], FILTER_VALIDATE_BOOLEAN) : true;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Quotation ID is required']);
    exit;
}

// Check if table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'quotations'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Quotations table not found']);
    exit;
}

// Get quotation
$sql = "SELECT * FROM quotations WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$quotation = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$quotation) {
    echo json_encode(['success' => false, 'error' => 'Quotation not found']);
    exit;
}

// Calculate GST
$amount = (float)($quotation['amount'] ?? 0);
$gstAmount = $amount * GST_RATE / 100;
$cgstAmount = $amount * GST_CGST / 100;
$sgstAmount = $amount * GST_SGST / 100;
$totalWithGst = $amount + $gstAmount;

$gstDetails = [
    'base_amount' => round($amount, 2),
    'gst_rate' => GST_RATE,
    'cgst_rate' => GST_CGST,
    'sgst_rate' => GST_SGST,
    'cgst_amount' => round($cgstAmount, 2),
    'sgst_amount' => round($sgstAmount, 2),
    'total_gst' => round($gstAmount, 2),
    'total_with_gst' => round($totalWithGst, 2)
];

// Response
echo json_encode([
    'success' => true,
    'message' => 'Quotation retrieved successfully',
    'data' => [
        'id' => (int)$quotation['id'],
        'quote_no' => $quotation['quote_no'] ?? '',
        'customer' => $quotation['customer'] ?? '',
        'customer_email' => $quotation['customer_email'] ?? '',
        'customer_phone' => $quotation['customer_phone'] ?? '',
        'service' => $quotation['service'] ?? 'Written Off',
        'amount' => round($amount, 2),
        'gst' => $include_gst ? $gstDetails : null,
        'total_with_gst' => $include_gst ? round($totalWithGst, 2) : round($amount, 2),
        'date' => $quotation['date'] ?? null,
        'validity' => $quotation['validity'] ?? null,
        'status' => $quotation['status'] ?? 'Draft',
        'notes' => $quotation['notes'] ?? '',
        'created_at' => $quotation['created_at'] ?? null,
        'updated_at' => $quotation['updated_at'] ?? null,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>