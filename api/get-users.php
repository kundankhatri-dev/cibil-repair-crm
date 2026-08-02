<?php
// ============================================================
// CIBIL REPAIR CRM - Get Users API (FIXED)
// Endpoint: /api/get_users.php
// Method: GET
// ============================================================

// ============================================================
// ERROR REPORTING
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ============================================================
// HEADERS
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('X-Content-Type-Options: nosniff');

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
// GET PARAMETERS
// ============================================================

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// Validate limit
if ($limit < 1 || $limit > 500) {
    $limit = 100;
}

// Validate offset
if ($offset < 0) {
    $offset = 0;
}

// ============================================================
// BUILD QUERY
// ============================================================

$isAdmin = in_array($userRole, ['admin', 'super_admin']);

// Base query
$query = "SELECT u.id, u.name, u.email, u.phone, u.role, u.unique_code, u.status, u.created_at,
          creator.name as created_by_name 
          FROM users u 
          LEFT JOIN users creator ON u.created_by = creator.id";

// Count query
$countQuery = "SELECT COUNT(*) as total FROM users u";

$where = [];
$params = [];
$types = '';

// Role-based access
if (!$isAdmin) {
    $where[] = "(u.created_by = ? OR u.id = ?)";
    $params[] = $userId;
    $params[] = $userId;
    $types .= 'ii';
}

// Search filter
if (!empty($search)) {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

// Role filter
if (!empty($roleFilter)) {
    $where[] = "u.role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}

// Status filter
if (!empty($statusFilter)) {
    $where[] = "u.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

// Build WHERE clause
if (!empty($where)) {
    $whereClause = " WHERE " . implode(' AND ', $where);
    $query .= $whereClause;
    $countQuery .= $whereClause;
}

// Add ORDER BY
$query .= " ORDER BY u.created_at DESC";

// Add LIMIT and OFFSET
$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// ============================================================
// EXECUTE QUERY
// ============================================================

try {
    // Prepare and execute main query
    $stmt = mysqli_prepare($conn, $query);
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Mask sensitive data for non-admin users
        if (!$isAdmin) {
            if (!empty($row['email'])) {
                $emailParts = explode('@', $row['email']);
                $row['masked_email'] = substr($emailParts[0], 0, 2) . '**@' . $emailParts[1];
            }
            if (!empty($row['phone'])) {
                $row['masked_phone'] = substr($row['phone'], 0, 2) . '****' . substr($row['phone'], -2);
            }
        }
        $users[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    // Get total count
    $countResult = mysqli_query($conn, $countQuery);
    $total = $countResult ? (int)mysqli_fetch_assoc($countResult)['total'] : 0;
    
    // ============================================================
    // RESPONSE
    // ============================================================
    
    echo json_encode([
        'success' => true,
        'message' => 'Users retrieved successfully',
        'users' => $users,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'search' => $search,
        'role_filter' => $roleFilter,
        'status_filter' => $statusFilter
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