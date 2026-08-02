<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['dispute_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;
$status = $input['status'] ?? '';
$notes = $input['notes'] ?? '';
$resolution_date = $input['resolution_date'] ?? '';

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
$set = "status = '$status', notes = CONCAT(notes, '\n', NOW(), ': ', '$notes')";
if ($resolution_date) $set .= ", resolution_date = '$resolution_date'";
if ($status == 'resolved') $set .= ", resolution_date = CURDATE()";
mysqli_query($conn, "UPDATE disputes SET $set WHERE id = $id");
echo json_encode(['success'=>true, 'message'=>'Status updated']);
mysqli_close($conn);
?>