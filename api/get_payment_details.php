<?php
// ============================================================
// CIBIL REPAIR CRM - Get Case API
// Endpoint: /api/get_case.php
// Method: GET
// ============================================================

// ============================================================
// CORS & HEADERS
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

// Handle preflight requests
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
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
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
// GET PARAMETERS
// ============================================================

$case_id = isset($_GET['caseId']) ? intval($_GET['caseId']) : 0;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Use either caseId or id parameter
$caseId = $case_id > 0 ? $case_id : $id;

if ($caseId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Case ID is required']);
    exit;
}

// ============================================================
// CHECK IF CASES TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'cases'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Cases table not found']);
    exit;
}

// ============================================================
// GET CASE DETAILS
// ============================================================

$sql = "SELECT id, case_no, payment_id, service_name, amount, status, client_name, client_email, client_phone, created_at, updated_at 
        FROM cases 
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $caseId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$case = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$case) {
    echo json_encode(['success' => false, 'error' => 'Case not found']);
    exit;
}

// ============================================================
// GET PAYMENT DETAILS (if payment_id exists)
// ============================================================

$payment = null;
if (!empty($case['payment_id'])) {
    $paymentSql = "SELECT id, transaction_id, order_id, amount, payment_method, payment_date, status as payment_status 
                   FROM payments 
                   WHERE id = ?";
    
    $paymentStmt = mysqli_prepare($conn, $paymentSql);
    mysqli_stmt_bind_param($paymentStmt, 'i', $case['payment_id']);
    mysqli_stmt_execute($paymentStmt);
    $paymentResult = mysqli_stmt_get_result($paymentStmt);
    $payment = mysqli_fetch_assoc($paymentResult);
    mysqli_stmt_close($paymentStmt);
}

// ============================================================
// GET CASE DOCUMENTS (optional)
// ============================================================

$documents = [];
$docSql = "SELECT id, document_name, file_path, file_size, uploaded_at 
           FROM case_documents 
           WHERE case_id = ? 
           ORDER BY uploaded_at DESC";

$docStmt = mysqli_prepare($conn, $docSql);
mysqli_stmt_bind_param($docStmt, 'i', $caseId);
mysqli_stmt_execute($docStmt);
$docResult = mysqli_stmt_get_result($docStmt);
while ($row = mysqli_fetch_assoc($docResult)) {
    $documents[] = $row;
}
mysqli_stmt_close($docStmt);

// ============================================================
// GET CASE ACTIVITY LOG
// ============================================================

$activities = [];
$actSql = "SELECT id, user_name, action, details, created_at 
           FROM activity_logs 
           WHERE details LIKE ? 
           ORDER BY created_at DESC 
           LIMIT 10";

$searchTerm = "%Case ID: $caseId%";
$actStmt = mysqli_prepare($conn, $actSql);
mysqli_stmt_bind_param($actStmt, 's', $searchTerm);
mysqli_stmt_execute($actStmt);
$actResult = mysqli_stmt_get_result($actStmt);
while ($row = mysqli_fetch_assoc($actResult)) {
    $activities[] = $row;
}
mysqli_stmt_close($actStmt);

// ============================================================
// FORMAT RESPONSE
// ============================================================

$response = [
    'success' => true,
    'data' => [
        'case' => [
            'id' => (int)$case['id'],
            'case_no' => $case['case_no'] ?? 'CASE-' . str_pad($case['id'], 6, '0', STR_PAD_LEFT),
            'service_name' => $case['service_name'] ?? '',
            'amount' => floatval($case['amount'] ?? 0),
            'status' => $case['status'] ?? 'pending',
            'client_name' => $case['client_name'] ?? '',
            'client_email' => $case['client_email'] ?? '',
            'client_phone' => $case['client_phone'] ?? '',
            'created_at' => $case['created_at'] ?? null,
            'updated_at' => $case['updated_at'] ?? null
        ]
    ]
];

// Add payment if exists
if ($payment) {
    $response['data']['payment'] = [
        'id' => (int)$payment['id'],
        'transaction_id' => $payment['transaction_id'] ?? '',
        'order_id' => $payment['order_id'] ?? '',
        'amount' => floatval($payment['amount'] ?? 0),
        'method' => $payment['payment_method'] ?? '',
        'status' => $payment['payment_status'] ?? 'pending',
        'date' => $payment['payment_date'] ?? null
    ];
} else {
    $response['data']['payment'] = null;
}

// Add documents if any
if (!empty($documents)) {
    $response['data']['documents'] = $documents;
}

// Add activities if any
if (!empty($activities)) {
    $response['data']['recent_activity'] = $activities;
}

// Add timestamp
$response['data']['generated_at'] = date('Y-m-d H:i:s');

// ============================================================
// SUCCESS RESPONSE
// ============================================================

echo json_encode($response);

mysqli_close($conn);
?>