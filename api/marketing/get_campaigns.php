<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

$campaign_type = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'all';

$where = [];
if ($campaign_type != 'all') {
    $where[] = "campaign_type = '$campaign_type'";
}
if ($status != 'all') {
    $where[] = "status = '$status'";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT * FROM marketing_campaigns $where_clause ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$campaigns = [];
while ($row = mysqli_fetch_assoc($result)) {
    $campaigns[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $campaigns,
    'total' => count($campaigns)
]);
?>