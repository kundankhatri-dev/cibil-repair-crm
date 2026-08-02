<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

// Get overall metrics
$query = "SELECT AVG(response_time_ms) as avg_response_time, COUNT(*) as total_requests, 
          SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as total_errors
          FROM it_api_logs WHERE created_at >= NOW() - INTERVAL 1 HOUR";
$result = mysqli_query($conn, $query);
$metrics = mysqli_fetch_assoc($result);

// Get per-endpoint metrics
$query2 = "SELECT endpoint, COUNT(*) as total_requests, AVG(response_time_ms) as avg_response_time,
           ROUND((SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as error_rate,
           ROUND((SUM(CASE WHEN status_code < 400 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as success_rate
           FROM it_api_logs WHERE created_at >= NOW() - INTERVAL 24 HOUR
           GROUP BY endpoint ORDER BY avg_response_time DESC LIMIT 20";
$result2 = mysqli_query($conn, $query2);
$endpoints = [];
while ($row = mysqli_fetch_assoc($result2)) {
    $endpoints[] = $row;
}

echo json_encode(['success' => true, 'data' => $metrics, 'endpoints' => $endpoints]);
?>