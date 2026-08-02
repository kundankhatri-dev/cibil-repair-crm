<?php
// ============================================================
// CIBIL REPAIR CRM - Save Document Analysis API
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Database connection
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    mysqli_close($conn);
    exit;
}

$documentType = isset($input['document_type']) ? trim($input['document_type']) : '';
$filename = isset($input['filename']) ? trim($input['filename']) : '';
$analysisResult = isset($input['analysis_result']) ? trim($input['analysis_result']) : '';

// Validate
if (empty($documentType) || empty($analysisResult)) {
    echo json_encode(['success' => false, 'error' => 'Document type and analysis result are required']);
    mysqli_close($conn);
    exit;
}

// Create table if not exists
$createTable = "CREATE TABLE IF NOT EXISTS document_analyses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    document_type VARCHAR(50),
    filename VARCHAR(255),
    analysis_result TEXT,
    status VARCHAR(20) DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $createTable);

// Insert
$sql = "INSERT INTO document_analyses (user_id, document_type, filename, analysis_result, status) 
        VALUES (?, ?, ?, ?, 'completed')";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'isss', $userId, $documentType, $filename, $analysisResult);

if (mysqli_stmt_execute($stmt)) {
    $id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Analysis saved successfully',
        'data' => [
            'id' => $id,
            'document_type' => $documentType,
            'filename' => $filename,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit;
?>