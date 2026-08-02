<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$status = $_GET['status'] ?? '';
$severity = $_GET['severity'] ?? '';

$where = [];
if ($status) $where[] = "c.status = '$status'";
if ($severity) $where[] = "c.severity = '$severity'";

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT c.*, cl.name as client_name, cat.category_name 
          FROM qa_complaints c
          JOIN clients cl ON c.client_id = cl.id
          JOIN qa_complaint_categories cat ON c.category_id = cat.id
          $where_clause
          ORDER BY c.created_at DESC";
$result = mysqli_query($conn, $query);
$complaints = [];
while ($row = mysqli_fetch_assoc($result)) {
    $complaints[] = $row;
}

echo json_encode(['success' => true, 'complaints' => $complaints, 'total' => count($complaints)]);
?>