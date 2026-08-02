<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT id, name, email FROM users WHERE role IN ('support_agent', 'credit_analyst', 'operations', 'agent') AND status = 'active' ORDER BY name";
$result = mysqli_query($conn, $query);
$agents = [];
while ($row = mysqli_fetch_assoc($result)) {
    $agents[] = $row;
}

echo json_encode(['success' => true, 'agents' => $agents]);
?>