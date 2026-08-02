<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$project_id = $data['project_id'] ?? 0;
$milestone_name = $data['milestone_name'] ?? '';
$target_date = $data['target_date'] ?? '';
$created_by = $data['created_by'] ?? 1;

if (!$project_id || !$milestone_name || !$target_date) {
    echo json_encode(['success' => false, 'error' => 'Project, milestone name, and target date required']);
    exit;
}

$query = "INSERT INTO pm_milestones (project_id, milestone_name, target_date, created_by, status) 
          VALUES ($project_id, '$milestone_name', '$target_date', $created_by, 'pending')";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Milestone added successfully']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>