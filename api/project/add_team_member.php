<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$project_id = $data['project_id'] ?? 0;
$user_id = $data['user_id'] ?? 0;
$role = $data['role'] ?? 'Team Member';
$allocation_percentage = $data['allocation_percentage'] ?? 100;

if (!$project_id || !$user_id) {
    echo json_encode(['success' => false, 'error' => 'Project ID and User ID required']);
    exit;
}

$query = "INSERT INTO pm_team_members (project_id, user_id, role, allocation_percentage, joined_at) 
          VALUES ($project_id, $user_id, '$role', $allocation_percentage, CURDATE())
          ON DUPLICATE KEY UPDATE is_active = 1, role = '$role'";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Team member added successfully']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>