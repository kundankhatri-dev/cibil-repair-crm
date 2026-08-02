<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT * FROM it_system_alerts ORDER BY severity DESC";
$result = mysqli_query($conn, $query);
$alerts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $alerts[] = $row;
}
echo json_encode(['success' => true, 'alerts' => $alerts]);
?>