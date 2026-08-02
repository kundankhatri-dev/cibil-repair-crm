<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['operations_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$title = $input['title'] ?? '';
$assignee_id = $input['assignee_id'] ?? 0;
$due_date = $input['due_date'] ?? date('Y-m-d');
$priority = $input['priority'] ?? 'medium';

if (!$title) {
    echo json_encode(['success' => false, 'error' => 'Task title required']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$query = "INSERT INTO operation_tasks (title, assigned_to, priority, due_date) VALUES ('$title', " . ($assignee_id ?: 'NULL') . ", '$priority', '$due_date')";
mysqli_query($conn, $query);
echo json_encode(['success' => true, 'message' => 'Task added']);
mysqli_close($conn);
?>