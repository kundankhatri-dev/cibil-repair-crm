<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT es.*, d.document_name 
          FROM dm_esignatures es
          JOIN dm_documents d ON es.document_id = d.id
          ORDER BY es.signed_at DESC";
$result = mysqli_query($conn, $query);

$signatures = [];
while ($row = mysqli_fetch_assoc($result)) {
    $signatures[] = $row;
}

echo json_encode(['success' => true, 'signatures' => $signatures, 'total' => count($signatures)]);
?>