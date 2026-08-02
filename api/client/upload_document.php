<?php
// api/client/upload_document.php - Upload document for client
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get client_id (only client can upload documents)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';

// Only client can upload documents
if ($viewer_role !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Only clients can upload documents']);
    exit;
}

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check if it's a POST request with multipart form data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST method allowed']);
    exit;
}

// ========== GET FORM DATA ==========
$document_name = trim($_POST['document_name'] ?? '');
$document_type = trim($_POST['document_type'] ?? '');
$case_id = isset($_POST['case_id']) ? (int)$_POST['case_id'] : null;
$notes = trim($_POST['notes'] ?? '');

// ========== VALIDATION ==========
$errors = [];

if (empty($document_name)) {
    $errors[] = "Document name is required";
} elseif (strlen($document_name) < 3) {
    $errors[] = "Document name must be at least 3 characters";
} elseif (strlen($document_name) > 200) {
    $errors[] = "Document name must be less than 200 characters";
}

$valid_document_types = [
    'Aadhar', 'PAN', 'Bank Statement', 'Income Proof', 
    'Credit Report', 'Loan NOC', 'Photo', 'Signature', 
    'Address Proof', 'Other'
];

if (empty($document_type)) {
    $errors[] = "Document type is required";
} elseif (!in_array($document_type, $valid_document_types)) {
    $errors[] = "Invalid document type selected";
}

// Check if file was uploaded
if (!isset($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
    $errors[] = "Please select a file to upload";
} elseif ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => "File is too large (server limit)",
        UPLOAD_ERR_FORM_SIZE => "File is too large (form limit)",
        UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
        UPLOAD_ERR_EXTENSION => "File upload stopped by extension"
    ];
    $error_msg = $upload_errors[$_FILES['document']['error']] ?? "Unknown upload error";
    $errors[] = $error_msg;
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

$file = $_FILES['document'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_size = $file['size'];
$file_error = $file['error'];

// ========== FILE VALIDATION ==========
$max_size = 5 * 1024 * 1024; // 5MB

if ($file_size > $max_size) {
    echo json_encode(['success' => false, 'error' => 'File size must be less than 5MB']);
    exit;
}

$allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: PDF, JPG, JPEG, PNG, DOC, DOCX']);
    exit;
}

$allowed_mime_types = [
    'application/pdf',
    'image/jpeg',
    'image/jpg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_tmp);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_mime_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type detected']);
    exit;
}

// ========== CREATE TABLES IF NOT EXISTS ==========
$create_table = "CREATE TABLE IF NOT EXISTS client_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    case_id INT,
    document_name VARCHAR(200) NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_name VARCHAR(500),
    file_path VARCHAR(500),
    file_size INT,
    file_type VARCHAR(100),
    notes TEXT,
    status ENUM('pending', 'verified', 'rejected', 'expired') DEFAULT 'pending',
    verification_notes TEXT,
    verified_by INT,
    verified_at DATETIME,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expiry_date DATE,
    is_deleted TINYINT DEFAULT 0,
    INDEX idx_client (client_id),
    INDEX idx_case (case_id),
    INDEX idx_type (document_type),
    INDEX idx_status (status)
)";

mysqli_query($conn, $create_table);

$create_logs = "CREATE TABLE IF NOT EXISTS document_verification_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    action VARCHAR(50),
    notes TEXT,
    performed_by INT,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_document (document_id)
)";

mysqli_query($conn, $create_logs);

// ========== CREATE UPLOAD DIRECTORY ==========
$upload_dir = '../uploads/client_documents/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Create subdirectory for this client
$client_dir = $upload_dir . 'client_' . $client_id . '/';
if (!file_exists($client_dir)) {
    mkdir($client_dir, 0777, true);
}

// ========== GENERATE UNIQUE FILE NAME ==========
$safe_name = preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($document_name, PATHINFO_FILENAME));
$unique_filename = time() . '_' . $client_id . '_' . $safe_name . '.' . $file_extension;
$file_path = $client_dir . $unique_filename;
$relative_path = 'uploads/client_documents/client_' . $client_id . '/' . $unique_filename;

// ========== MOVE UPLOADED FILE ==========
if (!move_uploaded_file($file_tmp, $file_path)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
    exit;
}

// ========== INSERT DOCUMENT RECORD ==========
$insert_query = "INSERT INTO client_documents (
                    client_id, case_id, document_name, document_type, 
                    file_name, file_path, file_size, file_type, notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, 
    "iissssiss", 
    $client_id, $case_id, $document_name, $document_type,
    $file_name, $relative_path, $file_size, $mime_type, $notes
);

$inserted = mysqli_stmt_execute($insert_stmt);
$document_id = mysqli_insert_id($conn);
mysqli_stmt_close($insert_stmt);

if (!$inserted) {
    // Delete the uploaded file if database insert fails
    unlink($file_path);
    echo json_encode(['success' => false, 'error' => 'Failed to save document information']);
    exit;
}

// ========== ADD TO VERIFICATION LOG ==========
$log_note = "Document uploaded and pending verification";
$add_log = mysqli_prepare($conn, "INSERT INTO document_verification_logs (document_id, action, notes, performed_by) VALUES (?, 'uploaded', ?, ?)");
mysqli_stmt_bind_param($add_log, "isi", $document_id, $log_note, $client_id);
mysqli_stmt_execute($add_log);
mysqli_stmt_close($add_log);

// ========== CHECK FOR REQUIRED DOCUMENTS ==========
$required_docs_query = "SELECT COUNT(*) as total, 
                        SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified
                        FROM client_documents 
                        WHERE client_id = ? AND document_type IN ('Aadhar', 'PAN') AND status = 'verified'";

$req_stmt = mysqli_prepare($conn, $required_docs_query);
mysqli_stmt_bind_param($req_stmt, "i", $client_id);
mysqli_stmt_execute($req_stmt);
$req_result = mysqli_stmt_get_result($req_stmt);
$req_data = mysqli_fetch_assoc($req_result);
mysqli_stmt_close($req_stmt);

// Update KYC status if both Aadhar and PAN are verified
if ($req_data['verified'] >= 2) {
    $update_kyc = mysqli_prepare($conn, "UPDATE client_profiles SET kyc_verified = 1, kyc_verified_at = NOW() WHERE client_id = ?");
    mysqli_stmt_bind_param($update_kyc, "i", $client_id);
    mysqli_stmt_execute($update_kyc);
    mysqli_stmt_close($update_kyc);
}

// ========== CREATE NOTIFICATION ==========
$notification_title = "Document Uploaded";
$notification_message = "Your document '$document_name' has been uploaded and is pending verification. This usually takes 1-2 business days.";

$add_notification = mysqli_prepare($conn, "INSERT INTO client_notifications (client_id, notification_type, title, message, link, priority) VALUES (?, 'document', ?, ?, ?, 'medium')");
$link = "client-dashboard.php?section=documents";
mysqli_stmt_bind_param($add_notification, "issss", $client_id, $notification_title, $notification_message, $link);
mysqli_stmt_execute($add_notification);
mysqli_stmt_close($add_notification);

// ========== LOG ACTIVITY ==========
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$log_activity = mysqli_prepare($conn, "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address, user_agent) VALUES (?, 'document_uploaded', ?, ?, ?)");
$desc = "Uploaded document: $document_name ($document_type)";
mysqli_stmt_bind_param($log_activity, "isss", $client_id, $desc, $ip_address, $user_agent);
mysqli_stmt_execute($log_activity);
mysqli_stmt_close($log_activity);

// ========== FORMAT FILE SIZE ==========
function formatFileSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'message' => 'Document uploaded successfully',
    'document' => [
        'id' => $document_id,
        'document_name' => $document_name,
        'document_type' => $document_type,
        'file_name' => $file_name,
        'file_size' => $file_size,
        'file_size_formatted' => formatFileSize($file_size),
        'status' => 'pending',
        'status_label' => 'Pending Verification',
        'uploaded_at' => date('Y-m-d H:i:s'),
        'uploaded_at_formatted' => date('d M Y h:i A')
    ],
    'next_steps' => [
        'Your document is pending verification',
        'Verification typically takes 1-2 business days',
        'You will be notified once verified',
        'You can track document status in the Documents section'
    ]
]);

mysqli_close($conn);
?>