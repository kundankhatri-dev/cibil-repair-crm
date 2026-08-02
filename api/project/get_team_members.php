<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$project_id = $_GET['project_id'] ?? '';

$where = $project_id ? "WHERE tm.project_id = $project_id" : "";

$query = "SELECT tm.*, p.project_name, u.name as user_name 
          FROM pm_team_members tm 
          JOIN pm_projects p ON tm.project_id = p.id 
          JOIN users u ON tm.user_id = u.id 
          $where 
          WHERE tm.is_active = 1 
          ORDER BY tm.joined_at DESC";

$result = mysqli_query($conn, $query);
$members = [];
while ($row = mysqli_fetch_assoc($result)) {
    $members[] = $row;
}

echo json_encode(['success' => true, 'members' => $members, 'total' => count($members)]);
?>