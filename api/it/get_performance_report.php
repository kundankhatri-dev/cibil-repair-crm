<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

// Get trends for chart (last 12 hours)
$query = "SELECT DATE_FORMAT(logged_at, '%H:00') as hour, 
          AVG(cpu_usage) as avg_cpu, AVG(memory_usage) as avg_memory, AVG(disk_usage) as avg_disk
          FROM it_system_health 
          WHERE logged_at >= NOW() - INTERVAL 12 HOUR
          GROUP BY hour ORDER BY hour";
$result = mysqli_query($conn, $query);
$trends = ['labels' => [], 'cpu' => [], 'memory' => [], 'disk' => []];
while ($row = mysqli_fetch_assoc($result)) {
    $trends['labels'][] = $row['hour'];
    $trends['cpu'][] = round($row['avg_cpu'], 1);
    $trends['memory'][] = round($row['avg_memory'], 1);
    $trends['disk'][] = round($row['avg_disk'], 1);
}
echo json_encode(['success' => true, 'trends' => $trends]);
?>