<?php
// ============================================================
// CIBIL REPAIR CRM - Get Activity Logs API
// ============================================================

// Disable error reporting for production
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
// CREATE ACTIVITY LOGS TABLE IF NOT EXISTS
// ============================================================

$createTable = "
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";
mysqli_query($conn, $createTable);

// ============================================================
// GET PARAMETERS
// ============================================================

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$actionFilter = isset($_GET['action']) ? trim($_GET['action']) : '';

// Validate
if ($limit < 1 || $limit > 500) $limit = 100;
if ($offset < 0) $offset = 0;

// ============================================================
// BUILD QUERY
// ============================================================

$isAdmin = in_array($userRole, ['admin', 'super_admin']);

// Base query
$query = "SELECT al.id, al.action, al.details, al.ip_address, al.user_agent, al.created_at, 
          u.name as user_name, u.email as user_email, u.role as user_role
          FROM activity_logs al 
          LEFT JOIN users u ON al.user_id = u.id";

$where = [];
$params = [];
$types = '';

// Role-based access
if (!$isAdmin) {
    $where[] = "al.user_id = ?";
    $params[] = $userId;
    $types .= 'i';
}

// Search filter
if (!empty($search)) {
    $where[] = "(al.action LIKE ? OR al.details LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ssss';
}

// Action filter
if (!empty($actionFilter)) {
    $where[] = "al.action = ?";
    $params[] = $actionFilter;
    $types .= 's';
}

// Build WHERE clause
if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

// Count query
$countQuery = "SELECT COUNT(*) as total FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id";
if (!empty($where)) {
    $countQuery .= " WHERE " . implode(' AND ', $where);
}

// Main query with ORDER BY and LIMIT
$query .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// ============================================================
// EXECUTE QUERY
// ============================================================

try {
    // Get total count
    $countResult = mysqli_query($conn, $countQuery);
    $total = $countResult ? (int)mysqli_fetch_assoc($countResult)['total'] : 0;
    
    // Get logs
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($conn));
    }
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $logs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = [
            'id' => (int)$row['id'],
            'action' => $row['action'] ?? '',
            'details' => $row['details'] ?? '',
            'ip_address' => $row['ip_address'] ?? '',
            'user_agent' => $row['user_agent'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'user' => [
                'id' => isset($row['user_id']) ? (int)$row['user_id'] : 0,
                'name' => $row['user_name'] ?? 'System',
                'email' => $row['user_email'] ?? '',
                'role' => $row['user_role'] ?? ''
            ]
        ];
    }
    mysqli_stmt_close($stmt);
    
    // ============================================================
    // RESPONSE
    // ============================================================
    
    echo json_encode([
        'success' => true,
        'data' => [
            'logs' => $logs,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'filters' => [
                'search' => $search,
                'action' => $actionFilter
            ],
            'stats' => [
                'total_logs' => $total
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

// ============================================================
// CLEANUP
// ============================================================

mysqli_close($conn);
exit;
?>