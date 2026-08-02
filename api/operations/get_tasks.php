<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['operations_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$query = "SELECT t.*, u.name as assignee_name FROM operation_tasks t LEFT JOIN users u ON t.assigned_to = u.id ORDER BY t.created_at DESC";
$result = mysqli_query($conn, $query);
$tasks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['due_date'] = date('d M Y', strtotime($row['due_date']));
    $tasks[] = $row;
}
echo json_encode(['success' => true, 'tasks' => $tasks]);
mysqli_close($conn);
?>