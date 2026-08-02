<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['dispute_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
$status = isset($_GET['status']) ? $_GET['status'] : '';
$query = "SELECT d.*, u.name as client_name FROM disputes d JOIN users u ON d.client_id = u.id";
if ($status) $query .= " WHERE d.status = '$status'";
$query .= " ORDER BY d.created_at DESC";
$result = mysqli_query($conn, $query);
$disputes = [];
while($row = mysqli_fetch_assoc($result)) $disputes[] = $row;
echo json_encode(['success'=>true, 'disputes'=>$disputes]);
mysqli_close($conn);
?>