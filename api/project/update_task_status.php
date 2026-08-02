<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$task_id = $data['task_id'] ?? 0;
$status = $data['status'] ?? '';

if (!$task_id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Task ID and status required']);
    exit;
}

$completed_at = $status == 'completed' ? ', completed_at = NOW()' : '';

$query = "UPDATE pm_tasks SET status = '$status' $completed_at WHERE id = $task_id";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Task status updated']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>