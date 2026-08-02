<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$project_id = $_GET['project_id'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];
if ($project_id) $where[] = "t.project_id = '$project_id'";
if ($status) $where[] = "t.status = '$status'";

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT t.*, p.project_name, u.name as assigned_to_name 
          FROM pm_tasks t 
          JOIN pm_projects p ON t.project_id = p.id 
          LEFT JOIN users u ON t.assigned_to = u.id 
          $where_clause 
          ORDER BY t.priority DESC, t.due_date ASC";

$result = mysqli_query($conn, $query);
$tasks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tasks[] = $row;
}

echo json_encode(['success' => true, 'tasks' => $tasks, 'total' => count($tasks)]);
?>