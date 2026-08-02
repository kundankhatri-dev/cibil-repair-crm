<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['loan_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
$query = "SELECT l.*, u.name as client_name, (SELECT cibil_score FROM credit_analysis WHERE client_id = l.client_id ORDER BY id DESC LIMIT 1) as cibil_score 
          FROM loan_applications l JOIN users u ON l.client_id = u.id ORDER BY l.created_at DESC";
$result = mysqli_query($conn, $query);
$apps = [];
while($row = mysqli_fetch_assoc($result)) $apps[] = $row;
echo json_encode(['success'=>true, 'applications'=>$apps]);
mysqli_close($conn);
?>