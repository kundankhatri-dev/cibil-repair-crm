<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['loan_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
$query = "SELECT l.*, u.name as client_name FROM loan_applications l JOIN users u ON l.client_id = u.id WHERE l.loan_type = 'Home Loan' ORDER BY l.created_at DESC";
$result = mysqli_query($conn, $query);
$loans = [];
while($row = mysqli_fetch_assoc($result)) {
    $loans[] = ['id'=>$row['id'],'client_name'=>$row['client_name'],'property_value'=>$row['amount']*1.25,'amount'=>$row['amount'],'tenure'=>$row['tenure'],'status'=>$row['status']];
}
echo json_encode(['success'=>true, 'loans'=>$loans]);
mysqli_close($conn);
?>