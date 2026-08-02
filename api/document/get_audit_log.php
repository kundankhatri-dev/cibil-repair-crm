<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$limit = $_GET['limit'] ?? 100;

$query = "SELECT al.*, d.document_name, u.name as user_name 
          FROM dm_audit_log al
          JOIN dm_documents d ON al.document_id = d.id
          JOIN users u ON al.user_id = u.id
          ORDER BY al.created_at DESC
          LIMIT $limit";
$result = mysqli_query($conn, $query);

$logs = [];
while ($row = mysqli_fetch_assoc($result)) {
    $logs[] = $row;
}

echo json_encode(['success' => true, 'logs' => $logs, 'total' => count($logs)]);
?>