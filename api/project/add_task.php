<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$project_id = $data['project_id'] ?? 0;
$task_name = $data['task_name'] ?? '';
$assigned_to = $data['assigned_to'] ?? null;
$priority = $data['priority'] ?? 'medium';
$due_date = $data['due_date'] ?? '';
$task_description = $data['task_description'] ?? '';
$created_by = $data['created_by'] ?? 1;

if (!$project_id || !$task_name || !$due_date) {
    echo json_encode(['success' => false, 'error' => 'Project, task name, and due date required']);
    exit;
}

$assigned_to_val = $assigned_to ? $assigned_to : 'NULL';

$query = "INSERT INTO pm_tasks (project_id, task_name, task_description, assigned_to, priority, due_date, created_by, status) 
          VALUES ($project_id, '$task_name', '$task_description', $assigned_to_val, '$priority', '$due_date', $created_by, 'pending')";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Task added successfully', 'task_id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>