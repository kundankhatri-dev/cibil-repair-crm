<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$type = $_GET['type'] ?? 'summary';

if ($type == 'summary') {
    $query = "SELECT AVG(cpu_usage) as avg_cpu, AVG(memory_usage) as avg_memory, AVG(disk_usage) as avg_disk FROM it_system_health WHERE logged_at >= NOW() - INTERVAL 1 HOUR";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    echo json_encode(['success' => true, 'data' => $data]);
} elseif ($type == 'servers') {
    $query = "SELECT * FROM it_system_Health ORDER BY logged_at DESC LIMIT 10";
    $result = mysqli_query($conn, $query);
    $servers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $servers[] = $row;
    }
    echo json_encode(['success' => true, 'servers' => $servers]);
} elseif ($type == 'all') {
    $query = "SELECT * FROM it_system_health WHERE logged_at >= NOW() - INTERVAL 24 HOUR ORDER BY server_name, logged_at DESC";
    $result = mysqli_query($conn, $query);
    $servers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $servers[] = $row;
    }
    echo json_encode(['success' => true, 'servers' => $servers]);
}
?>