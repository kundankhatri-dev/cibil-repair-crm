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
$gst = $input['gst'] ?? 0;
$total = $input['total'] ?? 0;

if (!$client_id || !$amount) {
    echo json_encode(['success' => false, 'error' => 'Client and amount required']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$invoice_no = 'INV' . date('Ymd') . rand(1000, 9999);
$query = "INSERT INTO invoices (client_id, invoice_no, amount, gst, total, issue_date, due_date) 
          VALUES ($client_id, '$invoice_no', $amount, $gst, $total, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY))";
mysqli_query($conn, $query);
echo json_encode(['success' => true, 'message' => 'Invoice generated', 'invoice_no' => $invoice_no]);
mysqli_close($conn);
?>