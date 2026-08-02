<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

// Get monthly project completion data
$query = "SELECT DATE_FORMAT(actual_end_date, '%Y-%m') as month, COUNT(*) as count 
          FROM pm_projects 
          WHERE status = 'completed' 
          AND actual_end_date IS NOT NULL 
          AND actual_end_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY DATE_FORMAT(actual_end_date, '%Y-%m')
          ORDER BY month";

$result = mysqli_query($conn, $query);
$performance = ['labels' => [], 'values' => []];
while ($row = mysqli_fetch_assoc($result)) {
    $performance['labels'][] = $row['month'];
    $performance['values'][] = (int)$row['count'];
}

echo json_encode(['success' => true, 'performance' => $performance]);
?>