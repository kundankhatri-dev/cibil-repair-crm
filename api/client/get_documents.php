<?php
// api/client/get_documents.php - Get all documents for client
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

// Get client_id (supports both client and partner/admin viewing)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';
$viewer_id = $_SESSION['user_id'] ?? null;

// If partner or admin viewing, allow client_id from GET
if (in_array($viewer_role, ['admin', 'partner']) && isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
    
    // Verify partner has access to this client
    if ($viewer_role === 'partner' && $viewer_id) {
        $check = mysqli_prepare($conn, "SELECT COUNT(*) FROM leads WHERE partner_id = ? AND customer_id = ?");
        mysqli_stmt_bind_param($check, "ii", $viewer_id, $client_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_bind_result($check, $count);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);
        
        if ($count == 0) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
    }
}

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// ========== CREATE DOCUMENTS TABLE IF NOT EXISTS ==========
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
    INDEX idx_status (status),
    INDEX idx_uploaded (uploaded_at)
)";

mysqli_query($conn, $create_table);

// ========== CREATE VERIFICATION LOGS TABLE ==========
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

// Get filter parameters
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$case_filter = isset($_GET['case_id']) ? (int)$_GET['case_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if ($limit < 1 || $limit > 200) {
    $limit = 50;
}

// ========== BUILD QUERY ==========
$query = "SELECT 
            d.*,
            DATE_FORMAT(d.uploaded_at, '%d %b %Y') as uploaded_date_formatted,
            DATE_FORMAT(d.uploaded_at, '%h:%i %p') as uploaded_time,
            DATE_FORMAT(d.verified_at, '%d %b %Y') as verified_date_formatted,
            CASE 
                WHEN d.file_size < 1024 THEN CONCAT(d.file_size, ' B')
                WHEN d.file_size < 1048576 THEN CONCAT(ROUND(d.file_size / 1024, 1), ' KB')
                ELSE CONCAT(ROUND(d.file_size / 1048576, 1), ' MB')
            END as size_formatted,
            u.name as verified_by_name
          FROM client_documents d
          LEFT JOIN users u ON d.verified_by = u.id
          WHERE d.client_id = ? AND d.is_deleted = 0";

$params = [$client_id];
$types = "i";

if ($type_filter !== 'all') {
    $query .= " AND d.document_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if ($status_filter !== 'all') {
    $query .= " AND d.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($case_filter > 0) {
    $query .= " AND d.case_id = ?";
    $params[] = $case_filter;
    $types .= "i";
}

if (!empty($search)) {
    $query .= " AND (d.document_name LIKE ? OR d.document_type LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
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

// ========== GET DOCUMENT TYPES AND COUNTS ==========
$types_query = "SELECT 
                    document_type,
                    COUNT(*) as count,
                    SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
                FROM client_documents 
                WHERE client_id = ? AND is_deleted = 0
                GROUP BY document_type
                ORDER BY document_type";

$types_stmt = mysqli_prepare($conn, $types_query);
mysqli_stmt_bind_param($types_stmt, "i", $client_id);
mysqli_stmt_execute($types_stmt);
$types_result = mysqli_stmt_get_result($types_stmt);
$document_types = mysqli_fetch_all($types_result, MYSQLI_ASSOC);
mysqli_stmt_close($types_stmt);

// ========== GET SUMMARY ==========
$summary_query = "SELECT 
                    COUNT(*) as total_documents,
                    SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                    SUM(file_size) as total_size
                  FROM client_documents 
                  WHERE client_id = ? AND is_deleted = 0";

$summary_stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($summary_stmt, "i", $client_id);
mysqli_stmt_execute($summary_stmt);
$summary_result = mysqli_stmt_get_result($summary_stmt);
$summary = mysqli_fetch_assoc($summary_result);
mysqli_stmt_close($summary_stmt);

// Format total size
$total_size_bytes = (int)($summary['total_size'] ?? 0);
if ($total_size_bytes < 1024) {
    $summary['total_size_formatted'] = $total_size_bytes . ' B';
} elseif ($total_size_bytes < 1048576) {
    $summary['total_size_formatted'] = round($total_size_bytes / 1024, 1) . ' KB';
} else {
    $summary['total_size_formatted'] = round($total_size_bytes / 1048576, 1) . ' MB';
}

// ========== GET TOTAL COUNT ==========
$total_query = "SELECT COUNT(*) as total FROM client_documents WHERE client_id = ? AND is_deleted = 0";
if ($type_filter !== 'all') {
    $total_query .= " AND document_type = ?";
}
if ($status_filter !== 'all') {
    $total_query .= " AND status = ?";
}
if ($case_filter > 0) {
    $total_query .= " AND case_id = ?";
}
if (!empty($search)) {
    $total_query .= " AND (document_name LIKE ? OR document_type LIKE ?)";
}

$total_stmt = mysqli_prepare($conn, $total_query);
$total_params = [$client_id];
$total_types = "i";

if ($type_filter !== 'all') {
    $total_params[] = $type_filter;
    $total_types .= "s";
}
if ($status_filter !== 'all') {
    $total_params[] = $status_filter;
    $total_types .= "s";
}
if ($case_filter > 0) {
    $total_params[] = $case_filter;
    $total_types .= "i";
}
if (!empty($search)) {
    $search_param = "%$search%";
    $total_params[] = $search_param;
    $total_params[] = $search_param;
    $total_types .= "ss";
}

mysqli_stmt_bind_param($total_stmt, $total_types, ...$total_params);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_data = mysqli_fetch_assoc($total_result);
$total_count = $total_data['total'] ?? 0;
mysqli_stmt_close($total_stmt);

// ========== GET REQUIRED DOCUMENTS CHECKLIST ==========
$required_docs = [
    ['type' => 'Aadhar', 'required' => true, 'description' => 'Government ID proof'],
    ['type' => 'PAN', 'required' => true, 'description' => 'PAN Card for income verification'],
    ['type' => 'Bank Statement', 'required' => false, 'description' => 'Last 6 months statement'],
    ['type' => 'Income Proof', 'required' => false, 'description' => 'Salary slips or ITR'],
    ['type' => 'Credit Report', 'required' => true, 'description' => 'Latest CIBIL report'],
    ['type' => 'Loan NOC', 'required' => false, 'description' => 'No Objection Certificate']
];

$checklist = [];
foreach ($required_docs as $req) {
    $found = false;
    $verified = false;
    foreach ($document_types as $dt) {
        if ($dt['document_type'] === $req['type']) {
            $found = true;
            $verified = $dt['verified_count'] > 0;
            break;
        }
    }
    $checklist[] = [
        'type' => $req['type'],
        'required' => $req['required'],
        'description' => $req['description'],
        'uploaded' => $found,
        'verified' => $verified,
        'status' => $verified ? 'verified' : ($found ? 'pending' : 'missing')
    ];
}

// ========== FORMAT DOCUMENTS ==========
$status_labels = [
    'pending' => 'Pending Verification',
    'verified' => 'Verified ✓',
    'rejected' => 'Rejected ✗',
    'expired' => 'Expired'
];

$status_colors = [
    'pending' => 'warning',
    'verified' => 'success',
    'rejected' => 'danger',
    'expired' => 'secondary'
];

$document_icons = [
    'Aadhar' => 'fa-id-card',
    'PAN' => 'fa-credit-card',
    'Bank Statement' => 'fa-university',
    'Income Proof' => 'fa-file-invoice-dollar',
    'Credit Report' => 'fa-chart-line',
    'Loan NOC' => 'fa-file-signature',
    'Other' => 'fa-file-alt'
];

foreach ($documents as &$doc) {
    $doc['status_label'] = $status_labels[$doc['status']] ?? ucfirst($doc['status']);
    $doc['status_badge'] = $status_colors[$doc['status']] ?? 'secondary';
    $doc['document_icon'] = $document_icons[$doc['document_type']] ?? 'fa-file';
    $doc['file_extension'] = strtoupper(pathinfo($doc['file_name'] ?? '', PATHINFO_EXTENSION));
    
    // Generate file URL
    if ($doc['file_path']) {
        $doc['file_url'] = $doc['file_path'];
        $doc['preview_url'] = "api/client/preview_document.php?id={$doc['id']}";
        $doc['download_url'] = "api/client/download_document.php?id={$doc['id']}";
    }
    
    // Check if document is about to expire
    if ($doc['expiry_date']) {
        $days_left = ceil((strtotime($doc['expiry_date']) - time()) / 86400);
        $doc['days_left'] = max(0, $days_left);
        if ($days_left <= 30 && $days_left > 0) {
            $doc['expiry_warning'] = "Expires in $days_left days";
        } elseif ($days_left <= 0) {
            $doc['expiry_warning'] = "EXPIRED";
        }
    }
}

// ========== GET RECENT UPLOADS (LAST 30 DAYS) ==========
$recent_query = "SELECT document_name, document_type, uploaded_at 
                 FROM client_documents 
                 WHERE client_id = ? AND is_deleted = 0 
                 AND uploaded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 ORDER BY uploaded_at DESC LIMIT 10";

$recent_stmt = mysqli_prepare($conn, $recent_query);
mysqli_stmt_bind_param($recent_stmt, "i", $client_id);
mysqli_stmt_execute($recent_stmt);
$recent_result = mysqli_stmt_get_result($recent_stmt);
$recent_uploads = mysqli_fetch_all($recent_result, MYSQLI_ASSOC);
mysqli_stmt_close($recent_stmt);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'data' => $documents,
    'total' => count($documents),
    'total_all' => (int)$total_count,
    'has_more' => ($offset + $limit) < $total_count,
    'summary' => [
        'total_documents' => (int)($summary['total_documents'] ?? 0),
        'verified_count' => (int)($summary['verified_count'] ?? 0),
        'pending_count' => (int)($summary['pending_count'] ?? 0),
        'rejected_count' => (int)($summary['rejected_count'] ?? 0),
        'total_size' => $summary['total_size_formatted'] ?? '0 B',
        'verification_rate' => ($summary['total_documents'] > 0) 
            ? round(($summary['verified_count'] / $summary['total_documents']) * 100) 
            : 0
    ],
    'document_types' => $document_types,
    'required_checklist' => $checklist,
    'recent_uploads' => $recent_uploads,
    'filters' => [
        'type' => $type_filter,
        'status' => $status_filter,
        'case_id' => $case_filter,
        'search' => $search,
        'limit' => $limit,
        'offset' => $offset
    ],
    'pagination' => [
        'current_page' => floor($offset / $limit) + 1,
        'per_page' => $limit,
        'total_pages' => ceil($total_count / $limit),
        'total_records' => (int)$total_count
    ],
    'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
    'max_upload_size' => 5 * 1024 * 1024, // 5MB
    'max_upload_size_formatted' => '5 MB'
]);

mysqli_close($conn);
?>