<?php
// ============================================================
// CIBIL REPAIR CRM - Get Registration Codes API
// Endpoint: /api/get_registration_codes.php
// Method: GET
// ============================================================

// ============================================================
// ERROR REPORTING (Production)
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ============================================================
// HEADERS
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// AUTHENTICATION
// ============================================================

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

// Check if user has permission
$allowedRoles = ['admin', 'super_admin', 'partner'];
if (!in_array($userRole, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden. Insufficient permissions.']);
    exit;
}

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// VALIDATE REQUEST METHOD
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use GET.']);
    mysqli_close($conn);
    exit;
}

// ============================================================
// CHECK IF TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'registration_codes'");

if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Registration codes table not found.',
        'message' => 'Please run the database migration to create the table.'
    ]);
    mysqli_close($conn);
    exit;
}

// ============================================================
// GET PARAMETERS
// ============================================================

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'created_at';
$sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'DESC';
$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

// Validate limit
if ($limit < 1 || $limit > 500) {
    $limit = 50;
}

// Validate offset
if ($offset < 0) {
    $offset = 0;
}

// ============================================================
// GET ACTUAL COLUMNS FROM TABLE
// ============================================================

$columns = [];
$columnResult = mysqli_query($conn, "SHOW COLUMNS FROM registration_codes");

if ($columnResult) {
    while ($row = mysqli_fetch_assoc($columnResult)) {
        $columns[] = $row['Field'];
    }
    mysqli_free_result($columnResult);
}

// Define columns we want to select
$desiredColumns = ['id', 'code', 'role', 'status', 'assigned_to_email', 'assigned_to_name', 
                   'used_by_user_id', 'used_at', 'created_by', 'created_at', 'expires_at', 'notes'];

// Only select columns that exist
$selectedColumns = array_intersect($desiredColumns, $columns);
if (empty($selectedColumns)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'No columns found in registration_codes table.']);
    mysqli_close($conn);
    exit;
}

// Always include id and code
if (!in_array('id', $selectedColumns)) {
    $selectedColumns[] = 'id';
}
if (!in_array('code', $selectedColumns)) {
    $selectedColumns[] = 'code';
}

$selectColumns = implode(', ', $selectedColumns);

// ============================================================
// GET SPECIFIC CODE BY ID
// ============================================================

if ($id > 0) {
    $query = "SELECT $selectColumns FROM registration_codes WHERE id = ?";
    $params = [$id];
    $types = 'i';
    
    // Non-admin users can only see their own codes
    if (!in_array($userRole, ['admin', 'super_admin'])) {
        $query .= " AND created_by = ?";
        $params[] = $userId;
        $types .= 'i';
    }
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $codeData = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($codeData) {
        echo json_encode(['success' => true, 'data' => $codeData]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Registration code not found or unauthorized.']);
    }
    mysqli_close($conn);
    exit;
}

// ============================================================
// BUILD QUERY WITH FILTERS
// ============================================================

$where = [];
$params = [];
$types = '';

// Role-based access
if (!in_array($userRole, ['admin', 'super_admin'])) {
    $where[] = "created_by = ?";
    $params[] = $userId;
    $types .= 'i';
}

// Status filter
if (!empty($status) && $status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

// Code search
if (!empty($code)) {
    $where[] = "code LIKE ?";
    $params[] = "%$code%";
    $types .= 's';
}

// Role filter (admin only)
if (!empty($role) && in_array($userRole, ['admin', 'super_admin']) && in_array('role', $columns)) {
    $where[] = "role = ?";
    $params[] = $role;
    $types .= 's';
}

// Email filter
if (!empty($email) && in_array('assigned_to_email', $columns)) {
    $where[] = "assigned_to_email LIKE ?";
    $params[] = "%$email%";
    $types .= 's';
}

// ============================================================
// VALIDATE SORT PARAMETERS
// ============================================================

$allowedSortColumns = array_intersect(['id', 'code', 'status', 'created_at', 'expires_at', 'used_at', 'role'], $selectedColumns);
if (!in_array($sort_by, $allowedSortColumns)) {
    $sort_by = 'created_at';
}
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

// ============================================================
// BUILD FINAL QUERY
// ============================================================

$query = "SELECT $selectColumns FROM registration_codes";

if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

$query .= " ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// ============================================================
// EXECUTE QUERY
// ============================================================

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$codes = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format data
    $formatted = [];
    foreach ($row as $key => $value) {
        if ($key === 'id' || $key === 'created_by' || $key === 'used_by_user_id') {
            $formatted[$key] = $value !== null ? (int)$value : null;
        } elseif ($key === 'used_at' || $key === 'created_at' || $key === 'expires_at') {
            $formatted[$key] = $value;
        } else {
            $formatted[$key] = $value ?? '';
        }
    }
    $codes[] = $formatted;
}
mysqli_stmt_close($stmt);

// ============================================================
// GET TOTAL COUNT
// ============================================================

$countQuery = "SELECT COUNT(*) as total FROM registration_codes";
if (!empty($where)) {
    // Remove LIMIT and OFFSET from where for count
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

$statusCounts = ['total' => $total];

if (in_array('status', $columns)) {
    $statuses = ['active', 'used', 'expired', 'revoked'];
    foreach ($statuses as $s) {
        $statusQuery = "SELECT COUNT(*) as count FROM registration_codes WHERE status = ?";
        $params = [$s];
        $types = 's';
        
        // Apply role-based filter for counts
        if (!in_array($userRole, ['admin', 'super_admin'])) {
            $statusQuery .= " AND created_by = ?";
            $params[] = $userId;
            $types .= 'i';
        }
        
        $stmt = mysqli_prepare($conn, $statusQuery);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $statusCounts[$s] = (int)$row['count'];
        mysqli_stmt_close($stmt);
    }
} else {
    $statusCounts['active'] = 0;
    $statusCounts['used'] = 0;
    $statusCounts['expired'] = 0;
    $statusCounts['revoked'] = 0;
}

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'data' => [
        'codes' => $codes,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'filters' => [
            'status' => $status,
            'code' => $code,
            'role' => $role,
            'email' => $email
        ],
        'sort' => [
            'by' => $sort_by,
            'order' => $sort_order
        ],
        'status_counts' => $statusCounts
    ]
]);

// ============================================================
// CLEANUP
// ============================================================

mysqli_close($conn);
exit;
?>