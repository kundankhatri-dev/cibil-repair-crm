<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$limit = $_GET['limit'] ?? 50;
$query = "SELECT * FROM it_slow_queries ORDER BY query_time_ms DESC LIMIT $limit";
$result = mysqli_query($conn, $query);
$queries = [];
while ($row = mysqli_fetch_assoc($result)) {
    $queries[] = $row;
}
echo json_encode(['success' => true, 'queries' => $queries]);
?>