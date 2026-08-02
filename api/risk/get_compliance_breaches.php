<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$status = $_GET['status'] ?? '';
$severity = $_GET['severity'] ?? '';

$where = [];
if ($status) $where[] = "status = '$status'";
if ($severity) $where[] = "severity = '$severity'";

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT * FROM risk_compliance_breaches $where_clause ORDER BY detected_at DESC";
$result = mysqli_query($conn, $query);

$breaches = [];
while ($row = mysqli_fetch_assoc($result)) {
    $breaches[] = $row;
}

echo json_encode(['success' => true, 'breaches' => $breaches, 'total' => count($breaches)]);
?>