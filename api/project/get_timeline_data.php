<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

// Get last 6 months task completion data
$query = "SELECT DATE_FORMAT(completed_at, '%Y-%m') as month, COUNT(*) as count 
          FROM pm_tasks 
          WHERE completed_at IS NOT NULL 
          AND completed_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY DATE_FORMAT(completed_at, '%Y-%m')
          ORDER BY month";

$result = mysqli_query($conn, $query);
$timeline = ['labels' => [], 'values' => []];
while ($row = mysqli_fetch_assoc($result)) {
    $timeline['labels'][] = $row['month'];
    $timeline['values'][] = (int)$row['count'];
}

echo json_encode(['success' => true, 'timeline' => $timeline]);
?>