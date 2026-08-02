<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$permission_id = $data['permission_id'] ?? 0;

if (!$permission_id) {
    echo json_encode(['success' => false, 'error' => 'Permission ID required']);
    exit;
}

$query = "DELETE FROM dm_document_permissions WHERE id = $permission_id";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Permission revoked']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>