<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['loan_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission) as t FROM loan_commission WHERE status='pending'"))['t'] ?? 0;
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission) as p FROM loan_commission WHERE status='pending'"))['p'] ?? 0;
$paid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission) as p FROM loan_commission WHERE status='paid'"))['p'] ?? 0;
$query = "SELECT c.*, l.client_id, u.name as client_name, l.amount as loan_amount FROM loan_commission c 
          JOIN loan_applications l ON c.loan_id = l.id JOIN users u ON l.client_id = u.id ORDER BY c.created_at DESC";
$result = mysqli_query($conn, $query);
$commissions = [];
while($row = mysqli_fetch_assoc($result)) $commissions[] = $row;
echo json_encode(['success'=>true, 'total'=>$total, 'pending'=>$pending, 'paid'=>$paid, 'commissions'=>$commissions]);
mysqli_close($conn);
?>