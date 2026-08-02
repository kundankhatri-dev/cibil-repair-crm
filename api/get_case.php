<?php
// ============================================================
// CIBIL REPAIR CRM - Get Case API
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

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

// Get parameters
$caseId = isset($_GET['caseId']) ? intval($_GET['caseId']) : 0;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$caseId = $caseId > 0 ? $caseId : $id;

if ($caseId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Case ID is required']);
    exit;
}

// Check if table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'cases'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Cases table not found']);
    exit;
}

// Get case
$sql = "SELECT * FROM cases WHERE id = ?";
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

// Get payment if exists
$payment = null;
if (!empty($case['payment_id'])) {
    $pSql = "SELECT * FROM payments WHERE id = ?";
    $pStmt = mysqli_prepare($conn, $pSql);
    mysqli_stmt_bind_param($pStmt, 'i', $case['payment_id']);
    mysqli_stmt_execute($pStmt);
    $pResult = mysqli_stmt_get_result($pStmt);
    $payment = mysqli_fetch_assoc($pResult);
    mysqli_stmt_close($pStmt);
}

// Response
echo json_encode([
    'success' => true,
    'data' => [
        'case' => $case,
        'payment' => $payment
    ]
]);

mysqli_close($conn);
?>