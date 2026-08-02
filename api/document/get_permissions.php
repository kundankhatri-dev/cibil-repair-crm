<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT dp.*, d.document_name, u.name as user_name, 
          (SELECT name FROM users WHERE id = dp.granted_by) as granted_by_name
          FROM dm_document_permissions dp
          JOIN dm_documents d ON dp.document_id = d.id
          JOIN users u ON dp.user_id = u.id
          WHERE dp.expires_at IS NULL OR dp.expires_at > NOW()
          ORDER BY dp.granted_at DESC";
$result = mysqli_query($conn, $query);

$permissions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $permissions[] = $row;
}

echo json_encode(['success' => true, 'permissions' => $permissions, 'total' => count($permissions)]);
?>