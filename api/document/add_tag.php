<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$tag_name = $data['tag_name'] ?? '';
$tag_color = $data['tag_color'] ?? '#6b7280';

if (!$tag_name) {
    echo json_encode(['success' => false, 'error' => 'Tag name required']);
    exit;
}

$query = "INSERT INTO dm_tags (tag_name, tag_color) VALUES ('$tag_name', '$tag_color')
          ON DUPLICATE KEY UPDATE tag_name = tag_name";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Tag added successfully']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>