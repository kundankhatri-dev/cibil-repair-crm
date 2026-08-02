<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['dispute_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$client_id = $input['client_id'] ?? 0;
$entity = $input['entity'] ?? '';
$issue_type = $input['issue_type'] ?? '';
$description = $input['description'] ?? '';

if (!$client_id || !$entity) { echo json_encode(['success' => false, 'error' => 'Missing fields']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
$dispute_no = 'DSP' . date('Ymd') . rand(1000,9999);
$query = "INSERT INTO disputes (client_id, dispute_no, entity, issue_type, description, status, submitted_date) VALUES ($client_id, '$dispute_no', '$entity', '$issue_type', '$description', 'submitted', CURDATE())";
mysqli_query($conn, $query);
echo json_encode(['success'=>true, 'message'=>'Dispute filed', 'dispute_no'=>$dispute_no]);
mysqli_close($conn);
?>