<?php
// api/partner/get_documents.php
// Partner Get Documents API - Retrieve uploaded documents

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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

// Verify user is actually a partner and get name
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

// ========== ENSURE DOCUMENTS TABLE EXISTS ==========
$documentsTable = 'partner_documents';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$documentsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS $documentsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        lead_id INT DEFAULT NULL,
        document_name VARCHAR(255) NOT NULL,
        document_type VARCHAR(50),
        file_path VARCHAR(500) NOT NULL,
        file_size INT,
        file_type VARCHAR(100),
        status ENUM('active', 'deleted') DEFAULT 'active',
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        INDEX idx_lead_id (lead_id),
        INDEX idx_document_type (document_type),
        INDEX idx_status (status)
    )";
    mysqli_query($conn, $createTable);
}

// ========== DETERMINE LEADS TABLE ==========
$leadsTable = 'partner_leads';
$checkLeadsTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkLeadsTable) == 0) {
    $leadsTable = 'leads';
}

// ========== GET INPUT PARAMETERS ==========
$lead_id = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : 0;
$document_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$document_type = isset($_GET['type']) ? trim($_GET['type']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

// Validate limit
if ($limit < 1 || $limit > 100) {
    $limit = 20;
}
$offset = ($page - 1) * $limit;

// Base URL for file downloads
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $protocol . $_SERVER['HTTP_HOST'];
$base_url = rtrim($domain, '/');

// ========== CASE 1: GET SPECIFIC DOCUMENT ==========
if ($document_id > 0) {
    $query = "SELECT 
                d.id,
                d.document_name,
                d.document_type,
                d.file_path,
                d.file_size,
                d.file_type,
                d.lead_id,
                DATE_FORMAT(d.uploaded_at, '%d-%m-%Y %h:%i %p') as uploaded_at,
                DATE_FORMAT(d.uploaded_at, '%Y-%m-%d') as uploaded_date,
                l.customer_name as lead_name
              FROM $documentsTable d
              LEFT JOIN $leadsTable l ON d.lead_id = l.id
              WHERE d.id = ? AND d.partner_id = ? AND d.status = 'active'";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $document_id, $partner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $document = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$document) {
        echo json_encode(['success' => false, 'error' => 'Document not found or access denied']);
        exit;
    }
    
    // Generate download URL
    $document['download_url'] = $base_url . '/uploads/partner_docs/' . basename($document['file_path']);
    $document['preview_url'] = $base_url . '/preview.php?file=' . urlencode($document['file_path']);
    $document['file_size_formatted'] = formatFileSize($document['file_size']);
    
    // Add document icon based on file type
    $document['icon'] = getFileIcon($document['file_type']);
    
    echo json_encode([
        'success' => true,
        'document' => $document
    ]);
    exit;
}

// ========== CASE 2: VERIFY LEAD ACCESS (if lead_id provided) ==========
if ($lead_id > 0) {
    $check_stmt = mysqli_prepare($conn, "SELECT id, customer_name FROM $leadsTable WHERE id = ? AND partner_id = ?");
    mysqli_stmt_bind_param($check_stmt, "ii", $lead_id, $partner_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) == 0) {
        echo json_encode(['success' => false, 'error' => 'Lead not found or access denied']);
        exit;
    }
    mysqli_stmt_close($check_stmt);
}

// ========== BUILD QUERY FOR DOCUMENTS LIST ==========
$query = "SELECT 
            d.id,
            d.document_name,
            d.document_type,
            d.file_path,
            d.file_size,
            d.file_type,
            d.lead_id,
            DATE_FORMAT(d.uploaded_at, '%d-%m-%Y') as uploaded_at,
            DATE_FORMAT(d.uploaded_at, '%Y-%m-%d %H:%i:%s') as uploaded_raw,
            l.customer_name as lead_name
          FROM $documentsTable d
          LEFT JOIN $leadsTable l ON d.lead_id = l.id
          WHERE d.partner_id = ? AND d.status = 'active'";

$params = [$partner_id];
$types = "i";

// Add lead filter
if ($lead_id > 0) {
    $query .= " AND d.lead_id = ?";
    $params[] = $lead_id;
    $types .= "i";
}

// Add document type filter
if (!empty($document_type)) {
    $query .= " AND d.document_type = ?";
    $params[] = $document_type;
    $types .= "s";
}

$query .= " ORDER BY d.uploaded_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$documents = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== GET TOTAL COUNT FOR PAGINATION ==========
$countQuery = "SELECT COUNT(*) as total FROM $documentsTable 
               WHERE partner_id = ? AND status = 'active'";
$countParams = [$partner_id];
$countTypes = "i";

if ($lead_id > 0) {
    $countQuery .= " AND lead_id = ?";
    $countParams[] = $lead_id;
    $countTypes .= "i";
}

if (!empty($document_type)) {
    $countQuery .= " AND document_type = ?";
    $countParams[] = $document_type;
    $countTypes .= "s";
}

$countStmt = mysqli_prepare($conn, $countQuery);
mysqli_stmt_bind_param($countStmt, $countTypes, ...$countParams);
mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$totalCount = mysqli_fetch_assoc($countResult)['total'] ?? 0;
mysqli_stmt_close($countStmt);

// ========== GET SUMMARY STATISTICS ==========
$summaryQuery = "SELECT 
                    COUNT(*) as total_documents,
                    SUM(file_size) as total_size,
                    COUNT(DISTINCT document_type) as unique_types
                  FROM $documentsTable 
                  WHERE partner_id = ? AND status = 'active'";
$summaryStmt = mysqli_prepare($conn, $summaryQuery);
mysqli_stmt_bind_param($summaryStmt, "i", $partner_id);
mysqli_stmt_execute($summaryStmt);
$summaryResult = mysqli_stmt_get_result($summaryStmt);
$summary = mysqli_fetch_assoc($summaryResult);
mysqli_stmt_close($summaryStmt);

// ========== GET DOCUMENT TYPE BREAKDOWN ==========
$typeQuery = "SELECT 
                document_type,
                COUNT(*) as count
              FROM $documentsTable 
              WHERE partner_id = ? AND status = 'active' AND document_type IS NOT NULL
              GROUP BY document_type
              ORDER BY count DESC";
$typeStmt = mysqli_prepare($conn, $typeQuery);
mysqli_stmt_bind_param($typeStmt, "i", $partner_id);
mysqli_stmt_execute($typeStmt);
$typeResult = mysqli_stmt_get_result($typeStmt);
$typeBreakdown = mysqli_fetch_all($typeResult, MYSQLI_ASSOC);
mysqli_stmt_close($typeStmt);

// ========== FORMAT DOCUMENTS ==========
foreach ($documents as &$doc) {
    $doc['file_size_formatted'] = formatFileSize($doc['file_size']);
    $doc['download_url'] = $base_url . '/uploads/partner_docs/' . basename($doc['file_path']);
    $doc['icon'] = getFileIcon($doc['file_type']);
    
    // Add document type label
    $typeLabels = [
        'kyc' => 'KYC Document',
        'agreement' => 'Agreement',
        'invoice' => 'Invoice',
        'report' => 'Credit Report',
        'other' => 'Other'
    ];
    $doc['type_label'] = $typeLabels[$doc['document_type']] ?? ucfirst($doc['document_type']);
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'documents' => $documents,
    'summary' => [
        'total_documents' => (int)($summary['total_documents'] ?? 0),
        'total_size_formatted' => formatFileSize($summary['total_size'] ?? 0),
        'unique_types' => (int)($summary['unique_types'] ?? 0)
    ],
    'type_breakdown' => $typeBreakdown,
    'filters' => [
        'lead_id' => $lead_id > 0 ? $lead_id : null,
        'document_type' => !empty($document_type) ? $document_type : null,
        'page' => $page,
        'limit' => $limit
    ],
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => (int)$totalCount,
        'total_pages' => ceil($totalCount / $limit),
        'has_next' => ($offset + $limit) < $totalCount,
        'has_previous' => $page > 1
    ]
]);

// ========== HELPER FUNCTIONS ==========
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

function getFileIcon($file_type) {
    if (strpos($file_type, 'pdf') !== false) return 'fa-file-pdf';
    if (strpos($file_type, 'image') !== false) return 'fa-file-image';
    if (strpos($file_type, 'word') !== false || strpos($file_type, 'doc') !== false) return 'fa-file-word';
    if (strpos($file_type, 'excel') !== false || strpos($file_type, 'sheet') !== false) return 'fa-file-excel';
    if (strpos($file_type, 'zip') !== false) return 'fa-file-archive';
    return 'fa-file';
}

mysqli_close($conn);
?>