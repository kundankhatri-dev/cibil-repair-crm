<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT * FROM it_backup_history ORDER BY started_at DESC LIMIT 20";
$result = mysqli_query($conn, $query);
$backups = [];
while ($row = mysqli_fetch_assoc($result)) {
    $backups[] = $row;
}
echo json_encode(['success' => true, 'backups' => $backups]);
?>