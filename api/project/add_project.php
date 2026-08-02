<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$project_name = $data['project_name'] ?? '';
$client_id = $data['client_id'] ?? 0;
$start_date = $data['start_date'] ?? date('Y-m-d');
$target_end_date = $data['target_end_date'] ?? '';
$project_type = $data['project_type'] ?? 'credit_repair';
$description = $data['description'] ?? '';
$project_manager = $data['project_manager'] ?? 1;

if (!$project_name || !$client_id || !$target_end_date) {
    echo json_encode(['success' => false, 'error' => 'Project name, client, and end date required']);
    exit;
}

$query = "INSERT INTO pm_projects (project_name, client_id, start_date, target_end_date, project_type, description, project_manager, created_by, status) 
          VALUES ('$project_name', $client_id, '$start_date', '$target_end_date', '$project_type', '$description', $project_manager, $project_manager, 'planning')";

if (mysqli_query($conn, $query)) {
    $project_id = mysqli_insert_id($conn);
    echo json_encode(['success' => true, 'message' => 'Project created successfully', 'project_id' => $project_id]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>