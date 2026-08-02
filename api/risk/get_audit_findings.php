<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$status = $_GET['status'] ?? '';
$audit_type = $_GET['audit_type'] ?? '';

$where = [];
if ($status) $where[] = "status = '$status'";
if ($audit_type) $where[] = "audit_type = '$audit_type'";

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT * FROM risk_audit_findings $where_clause ORDER BY discovered_date DESC";
$result = mysqli_query($conn, $query);

$findings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $findings[] = $row;
}

echo json_encode(['success' => true, 'findings' => $findings, 'total' => count($findings)]);
?>