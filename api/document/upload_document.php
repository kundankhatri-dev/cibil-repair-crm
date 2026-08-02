<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

// This is a simplified version. In production, handle file upload properly
$data = json_decode(file_get_contents('php://input'), true);

$folder_id = $data['folder_id'] ?? null;
$client_id = $data['client_id'] ?? null;
$document_name = $data['document_name'] ?? '';
$original_name = $data['original_name'] ?? '';
$document_type = $data['document_type'] ?? '';
$file_path = $data['file_path'] ?? '';
$file_size = $data['file_size'] ?? 0;
$mime_type = $data['mime_type'] ?? 'application/pdf';
$expiry_date = $data['expiry_date'] ?? null;
$uploaded_by = $data['uploaded_by'] ?? 1;

if (!$document_name || !$file_path) {
    echo json_encode(['success' => false, 'error' => 'Document name and file path required']);
    exit;
}

$folder_id_val = $folder_id ? $folder_id : 'NULL';
$client_id_val = $client_id ? $client_id : 'NULL';
$expiry_val = $expiry_date ? "'$expiry_date'" : 'NULL';

$query = "INSERT INTO dm_documents (folder_id, client_id, document_name, original_name, document_type, file_path, file_size, mime_type, expiry_date, uploaded_by, status) 
          VALUES ($folder_id_val, $client_id_val, '$document_name', '$original_name', '$document_type', '$file_path', $file_size, '$mime_type', $expiry_val, $uploaded_by, 'draft')";

if (mysqli_query($conn, $query)) {
    $doc_id = mysqli_insert_id($conn);
    echo json_encode(['success' => true, 'message' => 'Document uploaded successfully', 'document_id' => $doc_id]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>