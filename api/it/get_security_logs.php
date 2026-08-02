<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT * FROM it_failed_logins ORDER BY attempted_at DESC LIMIT 50";
$result = mysqli_query($conn, $query);
$logs = [];
while ($row = mysqli_fetch_assoc($result)) {
    $logs[] = $row;
}
echo json_encode(['success' => true, 'logs' => $logs]);
?>