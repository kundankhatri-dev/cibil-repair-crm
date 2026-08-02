<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['operations_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;
$status = $input['status'] ?? '';

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$completed = $status === 'completed' ? ', completed_at = NOW()' : '';
mysqli_query($conn, "UPDATE operation_tasks SET status = '$status' $completed WHERE id = $id");
echo json_encode(['success' => true, 'message' => 'Task updated']);
mysqli_close($conn);
?>