<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT * FROM dm_tags ORDER BY tag_name";
$result = mysqli_query($conn, $query);

$tags = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tags[] = $row;
}

echo json_encode(['success' => true, 'tags' => $tags, 'total' => count($tags)]);
?>