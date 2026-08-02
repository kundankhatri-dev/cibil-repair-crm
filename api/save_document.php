<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    mysqli_close($conn);
    exit;
}

$clientId = isset($input['client_id']) ? intval($input['client_id']) : 0;
$documentName = isset($input['document_name']) ? trim($input['document_name']) : '';
$documentType = isset($input['document_type']) ? trim($input['document_type']) : '';
$filePath = isset($input['file_path']) ? trim($input['file_path']) : '';

if ($clientId <= 0 || empty($documentName)) {
    echo json_encode(['success' => false, 'error' => 'Client ID and document name required']);
    mysqli_close($conn);
    exit;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS client_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    document_type VARCHAR(100),
    file_path VARCHAR(500),
    status VARCHAR(50) DEFAULT 'pending',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$query = "INSERT INTO client_documents (client_id, document_name, document_type, file_path, status) VALUES (?, ?, ?, ?, 'pending')";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "isss", $clientId, $documentName, $documentType, $filePath);

if (mysqli_stmt_execute($stmt)) {
    $id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Document saved successfully',
        'id' => $id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit;
?>