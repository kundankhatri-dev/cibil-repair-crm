<?php
// api/partner/upload_document.php
// Partner Upload Document API - Upload documents for leads

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuration
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'image/jpeg',
    'image/jpg',
    'image/png',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
]);
define('MAX_DOCS_PER_LEAD', 20);

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role, name FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$partner_name = $role_data['name'];

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);
$lead_id = isset($data['lead_id']) ? (int)$data['lead_id'] : 0;
$document_type = isset($data['document_type']) ? trim($data['document_type']) : '';
$document_name = isset($data['document_name']) ? trim($data['document_name']) : '';
$base64_content = isset($data['base64_content']) ? $data['base64_content'] : '';
$file_extension = isset($data['file_extension']) ? strtolower(trim($data['file_extension'])) : '';

// ========== VALIDATE INPUT ==========
if ($lead_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid Lead ID is required']);
    exit;
}

if (empty($document_type)) {
    echo json_encode(['success' => false, 'error' => 'Document type is required']);
    exit;
}

// Validate document type
$valid_types = ['kyc', 'agreement', 'invoice', 'report', 'other', 'credit_report', 'bank_statement', 'legal_notice'];
if (!in_array($document_type, $valid_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid document type']);
    exit;
}

if (empty($base64_content)) {
    echo json_encode(['success' => false, 'error' => 'File content is required']);
    exit;
}

// ========== VERIFY LEAD BELONGS TO PARTNER ==========
$leadsTable = 'partner_leads';
$checkLeadsTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkLeadsTable) == 0) {
    $leadsTable = 'leads';
}

$check_stmt = mysqli_prepare($conn, "SELECT id, customer_name FROM $leadsTable WHERE id = ? AND partner_id = ?");
mysqli_stmt_bind_param($check_stmt, "ii", $lead_id, $partner_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$lead = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

if (!$lead) {
    echo json_encode(['success' => false, 'error' => 'Lead not found or access denied']);
    exit;
}

// ========== CHECK DOCUMENT LIMIT PER LEAD ==========
$documentsTable = 'partner_documents';
$checkDocsTable = mysqli_query($conn, "SHOW TABLES LIKE '$documentsTable'");
if (mysqli_num_rows($checkDocsTable) > 0) {
    $count_query = "SELECT COUNT(*) as doc_count FROM $documentsTable WHERE lead_id = ? AND partner_id = ? AND status = 'active'";
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, "ii", $lead_id, $partner_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_data = mysqli_fetch_assoc($count_result);
    $doc_count = $count_data['doc_count'] ?? 0;
    mysqli_stmt_close($count_stmt);
    
    if ($doc_count >= MAX_DOCS_PER_LEAD) {
        echo json_encode(['success' => false, 'error' => 'Maximum ' . MAX_DOCS_PER_LEAD . ' documents allowed per lead']);
        exit;
    }
}

// ========== PROCESS BASE64 FILE ==========
// Remove base64 header if present
if (preg_match('/^data:([a-zA-Z0-9\/]+);base64,/', $base64_content, $matches)) {
    $mime_type = $matches[1];
    $base64_content = preg_replace('/^data:[a-zA-Z0-9\/]+;base64,/', '', $base64_content);
} else {
    $mime_type = '';
}

// Decode base64
$file_data = base64_decode($base64_content);
if ($file_data === false) {
    echo json_encode(['success' => false, 'error' => 'Invalid file data']);
    exit;
}

$file_size = strlen($file_data);

// Check file size
if ($file_size > MAX_FILE_SIZE) {
    $max_mb = MAX_FILE_SIZE / (1024 * 1024);
    echo json_encode(['success' => false, 'error' => "File size exceeds {$max_mb}MB limit"]);
    exit;
}

// Determine file extension from MIME type if not provided
if (empty($file_extension) && !empty($mime_type)) {
    $mime_to_ext = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'
    ];
    $file_extension = $mime_to_ext[$mime_type] ?? 'pdf';
}

// Validate file extension
if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS)]);
    exit;
}

// Sanitize document name
if (empty($document_name)) {
    $document_name = ucfirst($document_type) . '_' . date('Y-m-d');
}
$document_name = preg_replace('/[^a-zA-Z0-9\s\-_\.]/', '', $document_name);
if (strlen($document_name) > 255) {
    $document_name = substr($document_name, 0, 255);
}

// ========== CREATE UPLOAD DIRECTORY ==========
$upload_dir = __DIR__ . "/../uploads/partner_documents/{$partner_id}/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename (prevent duplicates)
$base_filename = pathinfo($document_name, PATHINFO_FILENAME);
$safe_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $base_filename);
$filename = date('Y-m-d_H-i-s') . '_' . $safe_filename . '_' . rand(1000, 9999) . '.' . $file_extension;
$filepath = $upload_dir . $filename;
$db_filepath = "uploads/partner_documents/{$partner_id}/{$filename}";

// Check for duplicate filename
$counter = 1;
while (file_exists($filepath)) {
    $filename = date('Y-m-d_H-i-s') . '_' . $safe_filename . '_' . $counter . '_' . rand(1000, 9999) . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    $db_filepath = "uploads/partner_documents/{$partner_id}/{$filename}";
    $counter++;
}

// ========== CREATE DOCUMENTS TABLE IF NOT EXISTS ==========
$checkDocsTable = mysqli_query($conn, "SHOW TABLES LIKE '$documentsTable'");
if (mysqli_num_rows($checkDocsTable) == 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS $documentsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        lead_id INT,
        document_name VARCHAR(255) NOT NULL,
        document_type VARCHAR(100) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT,
        file_type VARCHAR(50),
        status ENUM('active', 'deleted') DEFAULT 'active',
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        INDEX idx_lead_id (lead_id),
        INDEX idx_status (status),
        FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $create_table);
}

// ========== SAVE FILE ==========
if (file_put_contents($filepath, $file_data) === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file. Check directory permissions.']);
    exit;
}

// ========== INSERT DATABASE RECORD ==========
$insert_stmt = mysqli_prepare($conn, "INSERT INTO $documentsTable (partner_id, lead_id, document_name, document_type, file_path, file_size, file_type, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
mysqli_stmt_bind_param($insert_stmt, "iisssis", $partner_id, $lead_id, $document_name, $document_type, $db_filepath, $file_size, $file_extension);

if (mysqli_stmt_execute($insert_stmt)) {
    $doc_id = mysqli_insert_id($conn);
    
    // Log activity
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'upload_document', ?, NOW())");
        if ($log_stmt) {
            $description = "Uploaded document: $document_name ($document_type) for lead: " . $lead['customer_name'];
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Document uploaded successfully',
        'document' => [
            'id' => $doc_id,
            'name' => $document_name,
            'type' => $document_type,
            'path' => $db_filepath,
            'size' => $file_size,
            'size_formatted' => formatFileSize($file_size),
            'extension' => $file_extension,
            'uploaded_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    // Delete file if database insert fails
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
}

// ========== HELPER FUNCTION ==========
function formatFileSize($bytes) {
    if ($bytes === null || $bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

mysqli_stmt_close($insert_stmt);
mysqli_close($conn);
?>