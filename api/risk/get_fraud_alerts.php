<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$severity = $_GET['severity'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
if ($severity) $where[] = "severity = '$severity'";
if ($status) $where[] = "resolution_status = '$status'";
if ($search) $where[] = "(alert_details LIKE '%$search%' OR alert_type LIKE '%$search%')";

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT * FROM risk_fraud_alerts $where_clause ORDER BY triggered_at DESC LIMIT 100";
$result = mysqli_query($conn, $query);

$alerts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $alerts[] = $row;
}

echo json_encode(['success' => true, 'alerts' => $alerts, 'total' => count($alerts)]);
?>