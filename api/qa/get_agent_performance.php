<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

$where = "ap.date BETWEEN '$from' AND '$to'";
if ($search) $where .= " AND u.name LIKE '%$search%'";

$query = "SELECT ap.*, u.name as agent_name 
          FROM qa_agent_performance ap
          JOIN users u ON ap.agent_id = u.id
          WHERE $where
          ORDER BY ap.date DESC";
$result = mysqli_query($conn, $query);
$performance = [];
while ($row = mysqli_fetch_assoc($result)) {
    $performance[] = $row;
}

echo json_encode(['success' => true, 'performance' => $performance, 'total' => count($performance)]);
?>