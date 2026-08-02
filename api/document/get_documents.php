<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$folder_id = $_GET['folder_id'] ?? '';

$where = ["d.status != 'archived'"];
if ($search) $where[] = "(d.document_name LIKE '%$search%' OR d.document_id LIKE '%$search%')";
if ($type) $where[] = "d.mime_type LIKE '%$type%'";
if ($folder_id) $where[] = "d.folder_id = " . intval($folder_id);

$where_clause = "WHERE " . implode(" AND ", $where);

$query = "SELECT d.*, f.folder_name 
          FROM dm_documents d
          LEFT JOIN dm_folders f ON d.folder_id = f.id
          $where_clause
          ORDER BY d.uploaded_at DESC";

$result = mysqli_query($conn, $query);
$documents = [];
while ($row = mysqli_fetch_assoc($result)) {
    $documents[] = $row;
}

echo json_encode(['success' => true, 'documents' => $documents, 'total' => count($documents)]);
?>