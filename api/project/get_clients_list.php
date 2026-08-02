<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT id, name FROM clients ORDER BY name";
$result = mysqli_query($conn, $query);
$clients = [];
while ($row = mysqli_fetch_assoc($result)) {
    $clients[] = $row;
}

echo json_encode(['success' => true, 'clients' => $clients]);
?>