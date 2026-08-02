<?php
// ============================================================
// CIBIL REPAIR CRM - Upload Client Document API
// ============================================================

// Disable error display
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Database connection
$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// CREATE TABLE IF NOT EXISTS
// ============================================================

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS client_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT,
    client_email VARCHAR(100),
    doc_type ENUM('pan','aadhar','cibil') DEFAULT 'cibil',
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    file_data LONGTEXT,
    file_type VARCHAR(50),
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_id (client_id),
    INDEX idx_client_email (client_email),
    INDEX idx_doc_type (doc_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ============================================================
// GET POST DATA
// ============================================================

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$clientId = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
$docType = isset($_POST['doc_type']) ? trim($_POST['doc_type']) : 'cibil';

// Validate doc_type
$allowedDocTypes = ['pan', 'aadhar', 'cibil'];
if (!in_array($docType, $allowedDocTypes)) {
    $docType = 'cibil';
}

// Validate email or client_id
if (empty($email) && $clientId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Email or client_id is required']);
    mysqli_close($conn);
    exit;
}

// ============================================================
// VALIDATE FILE UPLOAD
// ============================================================

if (!isset($_FILES['document'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    mysqli_close($conn);
    exit;
}

if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
        UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    $errorMsg = $errors[$_FILES['document']['error']] ?? 'Unknown upload error';
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    mysqli_close($conn);
    exit;
}

$file = $_FILES['document'];
$fileName = basename($file['name']);
$fileType = $file['type'];
$fileTmpPath = $file['tmp_name'];

// Limit file size to 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit']);
    mysqli_close($conn);
    exit;
}

// ============================================================
// READ FILE CONTENT
// ============================================================

$fileData = file_get_contents($fileTmpPath);

if ($fileData === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to read file content']);
    mysqli_close($conn);
    exit;
}

// For text files, store as is; for binaries, base64 encode
$isTextFile = in_array($fileType, [
    'text/plain', 
    'text/csv', 
    'application/json', 
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

if (!$isTextFile) {
    $fileData = base64_encode($fileData);
}

// ============================================================
// SAVE TO DATABASE
// ============================================================

$query = "INSERT INTO client_documents (client_id, client_email, doc_type, file_name, file_type, file_data, uploaded_by, uploaded_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "isssssi", $clientId, $email, $docType, $fileName, $fileType, $fileData, $userId);

if (mysqli_stmt_execute($stmt)) {
    $docId = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Document uploaded successfully',
        'data' => [
            'id' => $docId,
            'client_email' => $email,
            'doc_type' => $docType,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'uploaded_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save document: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
exit;
?>