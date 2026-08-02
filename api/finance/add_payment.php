<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['finance_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$client_id = $input['client_id'] ?? 0;
$package_name = $input['package_name'] ?? '';
$amount = $input['amount'] ?? 0;
$payment_date = $input['payment_date'] ?? date('Y-m-d');
$payment_mode = $input['payment_mode'] ?? 'UPI';
$transaction_id = $input['transaction_id'] ?? '';

if (!$client_id || !$amount) {
    echo json_encode(['success' => false, 'error' => 'Client and amount required']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$query = "INSERT INTO payments (client_id, package, amount, payment_mode, transaction_id, status, payment_date) 
          VALUES ($client_id, '$package_name', $amount, '$payment_mode', '$transaction_id', 'paid', '$payment_date')";
mysqli_query($conn, $query);
echo json_encode(['success' => true, 'message' => 'Payment recorded']);
mysqli_close($conn);
?>