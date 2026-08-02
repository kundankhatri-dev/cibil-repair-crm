<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT id, name, email FROM clients WHERE status = 'active' ORDER BY name LIMIT 500";
$result = mysqli_query($conn, $query);
$clients = [];
while ($row = mysqli_fetch_assoc($result)) {
    $clients[] = $row;
}

echo json_encode(['success' => true, 'clients' => $clients]);
?>