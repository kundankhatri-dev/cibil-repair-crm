<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Document ID required']);
    exit;
}

$check = mysqli_query($conn, "SELECT id FROM client_documents WHERE id = $id");
if (mysqli_num_rows($check) == 0) {
    echo json_encode(['success' => false, 'error' => 'Document not found']);
    exit;
}

$doc_type = isset($input['doc_type']) ? mysqli_real_escape_string($conn, trim($input['doc_type'])) : '';
$file_name = isset($input['file_name']) ? mysqli_real_escape_string($conn, trim($input['file_name'])) : '';
$file_path = isset($input['file_path']) ? mysqli_real_escape_string($conn, trim($input['file_path'])) : '';
$status = isset($input['status']) ? mysqli_real_escape_string($conn, trim($input['status'])) : '';
$client_id = isset($input['client_id']) ? intval($input['client_id']) : 0;

$updates = array();
if (!empty($doc_type)) $updates[] = "doc_type = '$doc_type'";
if (!empty($file_name)) $updates[] = "file_name = '$file_name'";
if (!empty($file_path)) $updates[] = "file_path = '$file_path'";
if (!empty($status)) $updates[] = "status = '$status'";
if ($client_id > 0) $updates[] = "client_id = $client_id";

if (empty($updates)) {
    echo json_encode(['success' => false, 'error' => 'No fields to update']);
    exit;
}

$sql = "UPDATE client_documents SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    $result = mysqli_query($conn, "SELECT * FROM client_documents WHERE id = $id");
    $document = mysqli_fetch_assoc($result);
    echo json_encode([
        'success' => true,
        'message' => 'Document updated',
        'document' => $document
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>