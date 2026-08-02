<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['loan_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;
$status = $input['status'] ?? '';
$sanctioned_amount = $input['sanctioned_amount'] ?? 0;
$bank = $input['bank'] ?? '';
$notes = $input['notes'] ?? '';

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
$set = "status = '$status', notes = CONCAT(notes, '\n', NOW(), ': ', '$notes')";
if ($status == 'approved') {
    $set .= ", sanctioned_amount = $sanctioned_amount, approved_date = CURDATE()";
    if ($bank) $set .= ", bank = '$bank'";
    $commission = $sanctioned_amount * 0.01;
    mysqli_query($conn, "INSERT INTO loan_commission (loan_id, commission) VALUES ($id, $commission)");
}
mysqli_query($conn, "UPDATE loan_applications SET $set WHERE id = $id");
echo json_encode(['success'=>true, 'message'=>'Status updated']);
mysqli_close($conn);
?>