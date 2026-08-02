<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$document_id = $data['document_id'] ?? 0;
$user_id = $data['user_id'] ?? 0;
$permission_type = $data['permission_type'] ?? '';
$granted_by = $data['granted_by'] ?? 1;
$expires_at = $data['expires_at'] ?? null;

if (!$document_id || !$user_id || !$permission_type) {
    echo json_encode(['success' => false, 'error' => 'Document ID, User ID, and permission type required']);
    exit;
}

$expires_val = $expires_at ? "'$expires_at'" : 'NULL';

$query = "INSERT INTO dm_document_permissions (document_id, user_id, permission_type, granted_by, expires_at) 
          VALUES ($document_id, $user_id, '$permission_type', $granted_by, $expires_val)
          ON DUPLICATE KEY UPDATE granted_at = NOW(), expires_at = $expires_val";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Permission granted']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>