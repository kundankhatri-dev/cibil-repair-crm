<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

// Get last 30 days trend
$query = "SELECT DATE(triggered_at) as date, COUNT(*) as count 
          FROM risk_fraud_alerts 
          WHERE triggered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          GROUP BY DATE(triggered_at)
          ORDER BY date";
$result = mysqli_query($conn, $query);

$trends = ['labels' => [], 'values' => []];
while ($row = mysqli_fetch_assoc($result)) {
    $trends['labels'][] = $row['date'];
    $trends['values'][] = (int)$row['count'];
}

echo json_encode(['success' => true, 'trends' => $trends]);
?>