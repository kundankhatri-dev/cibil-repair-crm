<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Campaign ID required']);
    exit;
}

$query = "DELETE FROM marketing_campaigns WHERE id = $id";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Campaign deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete: ' . mysqli_error($conn)]);
}
?>