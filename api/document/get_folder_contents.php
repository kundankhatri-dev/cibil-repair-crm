<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$folder_id = $_GET['folder_id'] ?? 0;

if (!$folder_id) {
    echo json_encode(['success' => false, 'error' => 'Folder ID required']);
    exit;
}

$query = "SELECT d.* FROM dm_documents d WHERE d.folder_id = $folder_id AND d.status != 'archived' ORDER BY d.uploaded_at DESC";
$result = mysqli_query($conn, $query);

$documents = [];
while ($row = mysqli_fetch_assoc($result)) {
    $documents[] = $row;
}

echo json_encode(['success' => true, 'documents' => $documents, 'total' => count($documents)]);
?>