<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['loan_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$client_id = $input['client_id'] ?? 0;
$loan_type = $input['loan_type'] ?? '';
$amount = $input['amount'] ?? 0;
$tenure = $input['tenure'] ?? 0;
$bank = $input['bank'] ?? '';

if (!$client_id || !$amount) { echo json_encode(['success' => false, 'error' => 'Missing fields']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
$query = "INSERT INTO loan_applications (client_id, loan_type, amount, tenure, bank, status) VALUES ($client_id, '$loan_type', $amount, $tenure, '$bank', 'pending')";
mysqli_query($conn, $query);
echo json_encode(['success'=>true, 'message'=>'Application submitted']);
mysqli_close($conn);
?>