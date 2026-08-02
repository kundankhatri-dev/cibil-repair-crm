<?php
// ============================================================
// CIBIL REPAIR CRM - Add Payment API
// Endpoint: /api/add_payment.php
// Method: POST
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Validate required fields
$clientName = isset($input['clientName']) ? trim($input['clientName']) : '';
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$service = isset($input['service']) ? trim($input['service']) : '';
$status = isset($input['status']) ? trim($input['status']) : 'pending';
$payment_mode = isset($input['payment_mode']) ? trim($input['payment_mode']) : '';
$transaction_id = isset($input['transaction_id']) ? trim($input['transaction_id']) : '';
$package = isset($input['package']) ? trim($input['package']) : '';

if (empty($clientName)) {
    echo json_encode(['success' => false, 'error' => 'Client name is required']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Amount must be greater than 0']);
    exit;
}

// Insert payment
$sql = "INSERT INTO payments (user_id, clientName, amount, service, status, payment_mode, transaction_id, package, date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'isdsssss', $user_id, $clientName, $amount, $service, $status, $payment_mode, $transaction_id, $package);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    $id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Payment added successfully',
        'data' => [
            'id' => $id,
            'user_id' => $user_id,
            'clientName' => $clientName,
            'amount' => $amount,
            'service' => $service,
            'status' => $status
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add payment']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>