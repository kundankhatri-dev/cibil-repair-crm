<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT * FROM dm_folders ORDER BY folder_name";
$result = mysqli_query($conn, $query);

$folders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $folders[] = $row;
}

echo json_encode(['success' => true, 'folders' => $folders, 'total' => count($folders)]);
?>