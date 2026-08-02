<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$project_id = $_GET['project_id'] ?? '';

$where = $project_id ? "WHERE m.project_id = $project_id" : "";

$query = "SELECT m.*, p.project_name 
          FROM pm_milestones m 
          JOIN pm_projects p ON m.project_id = p.id 
          $where 
          ORDER BY m.target_date ASC";

$result = mysqli_query($conn, $query);
$milestones = [];
while ($row = mysqli_fetch_assoc($result)) {
    $milestones[] = $row;
}

echo json_encode(['success' => true, 'milestones' => $milestones, 'total' => count($milestones)]);
?>