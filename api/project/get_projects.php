<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
if ($status) $where[] = "p.status = '$status'";
if ($search) $where[] = "(p.project_name LIKE '%$search%' OR p.project_code LIKE '%$search%')";

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT p.*, c.name as client_name 
          FROM pm_projects p 
          LEFT JOIN clients c ON p.client_id = c.id 
          $where_clause 
          ORDER BY p.created_at DESC";

$result = mysqli_query($conn, $query);
$projects = [];
while ($row = mysqli_fetch_assoc($result)) {
    $projects[] = $row;
}

echo json_encode(['success' => true, 'projects' => $projects, 'total' => count($projects)]);
?>