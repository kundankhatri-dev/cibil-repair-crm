<?php
// ============================================================
// CIBIL REPAIR CRM - Get Document Analyses API
// ============================================================

// Disable error display
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

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

$createTable = "
CREATE TABLE IF NOT EXISTS document_analyses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    document_type VARCHAR(50),
    filename VARCHAR(255),
    file_path VARCHAR(255),
    file_size VARCHAR(20),
    analysis_result TEXT,
    status ENUM('pending','processing','completed','failed') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";
mysqli_query($conn, $createTable);

// ============================================================
// GET PARAMETERS
// ============================================================

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Validate
if ($limit < 1 || $limit > 500) $limit = 50;
if ($offset < 0) $offset = 0;

// ============================================================
// BUILD QUERY
// ============================================================

$isAdmin = in_array($userRole, ['admin', 'super_admin']);

$where = [];
$params = [];
$types = '';

// Role-based access
if (!$isAdmin) {
    $where[] = "user_id = ?";
    $params[] = $userId;
    $types .= 'i';
}

// Search filter
if (!empty($search)) {
    $where[] = "(document_type LIKE ? OR filename LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

// Build WHERE clause
if (!empty($where)) {
    $whereClause = " WHERE " . implode(' AND ', $where);
} else {
    $whereClause = "";
}

$query = "SELECT id, user_id, document_type, filename, file_path, file_size, 
          analysis_result, status, created_at 
          FROM document_analyses 
          $whereClause 
          ORDER BY created_at DESC 
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// ============================================================
// EXECUTE QUERY
// ============================================================

try {
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($conn));
    }
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $analyses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $analyses[] = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'document_type' => $row['document_type'] ?? '',
            'filename' => $row['filename'] ?? '',
            'file_path' => $row['file_path'] ?? '',
            'file_size' => $row['file_size'] ?? '',
            'analysis_result' => $row['analysis_result'] ?? '',
            'status' => $row['status'] ?? 'completed',
            'created_at' => $row['created_at'] ?? ''
        ];
    }
    mysqli_stmt_close($stmt);
    
    // ============================================================
    // GET TOTAL COUNT
    // ============================================================
    
    $countQuery = "SELECT COUNT(*) as total FROM document_analyses";
    if (!empty($where)) {
        $countWhere = array_slice($where, 0, count($where) - 2);
        if (!empty($countWhere)) {
            $countQuery .= " WHERE " . implode(' AND ', $countWhere);
        }
    }
    
    $countResult = mysqli_query($conn, $countQuery);
    $total = $countResult ? (int)mysqli_fetch_assoc($countResult)['total'] : 0;
    
    // ============================================================
    // GET STATUS COUNTS
    // ============================================================
    
    $statusQuery = "SELECT status, COUNT(*) as count FROM document_analyses";
    if (!empty($where)) {
        $statusWhere = array_slice($where, 0, count($where) - 2);
        if (!empty($statusWhere)) {
            $statusQuery .= " WHERE " . implode(' AND ', $statusWhere);
        }
    }
    $statusQuery .= " GROUP BY status";
    
    $statusResult = mysqli_query($conn, $statusQuery);
    $statusCounts = [];
    while ($row = mysqli_fetch_assoc($statusResult)) {
        $statusCounts[$row['status']] = (int)$row['count'];
    }
    
    // ============================================================
    // RESPONSE
    // ============================================================
    
    echo json_encode([
        'success' => true,
        'data' => [
            'analyses' => $analyses,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'filters' => [
                'search' => $search
            ],
            'status_counts' => $statusCounts
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
exit;
?>