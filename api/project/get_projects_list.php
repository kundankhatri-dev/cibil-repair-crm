<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT id, project_name, project_code FROM pm_projects WHERE status IN ('planning', 'in_progress') ORDER BY project_name";
$result = mysqli_query($conn, $query);
$projects = [];
while ($row = mysqli_fetch_assoc($result)) {
    $projects[] = $row;
}

echo json_encode(['success' => true, 'projects' => $projects]);
?>