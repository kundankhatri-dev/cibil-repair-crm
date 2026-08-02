<?php
// ============================================================
// CIBIL REPAIR CRM - Get Users API
// Endpoint: /api/get_users.php
// Method: GET
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
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
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// ============================================================
// CHECK IF TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Users table not found']);
    exit;
}

// ============================================================
// GET PARAMETERS
// ============================================================

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'id';
$sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'DESC';

// Validate
if ($limit < 1) $limit = 10;
if ($limit > 500) $limit = 100;
if ($offset < 0) $offset = 0;

// ============================================================
// BUILD QUERY
// ============================================================

$where = [];
$params = [];
$types = '';

// Search filter
if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

// Role filter
if (!empty($role) && $role !== 'all') {
    $where[] = "role = ?";
    $params[] = $role;
    $types .= 's';
}

// Status filter
if (!empty($status) && $status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ============================================================
// GET USERS
// ============================================================

$query = "SELECT id, name, email, phone, role, status, created_at, updated_at, last_login 
          FROM users $whereClause ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = [
        'id' => intval($row['id']),
        'name' => $row['name'],
        'email' => $row['email'],
        'phone' => $row['phone'] ?? '',
        'role' => $row['role'] ?? 'client',
        'status' => $row['status'] ?? 'active',
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
        'last_login' => $row['last_login'] ?? null
    ];
}
mysqli_stmt_close($stmt);

// ============================================================
// GET TOTAL COUNT
// ============================================================

$countWhere = array_slice($where, 0, count($where) - 2);
$countClause = !empty($countWhere) ? 'WHERE ' . implode(' AND ', $countWhere) : '';

$countQuery = "SELECT COUNT(*) as total FROM users $countClause";
$countResult = mysqli_query($conn, $countQuery);
$total = $countResult ? (int)mysqli_fetch_assoc($countResult)['total'] : 0;

// ============================================================
// GET ROLE COUNTS
// ============================================================

$roleCounts = [];
$roles = ['admin', 'super_admin', 'partner', 'client', 'employee'];
foreach ($roles as $r) {
    $rResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = '$r'");
    $rRow = mysqli_fetch_assoc($rResult);
    $roleCounts[$r] = $rRow ? intval($rRow['count']) : 0;
}

// ============================================================
// GET STATUS COUNTS
// ============================================================

$statusCounts = [];
$statuses = ['active', 'inactive', 'pending', 'suspended'];
foreach ($statuses as $s) {
    $sResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE status = '$s'");
    $sRow = mysqli_fetch_assoc($sResult);
    $statusCounts[$s] = $sRow ? intval($sRow['count']) : 0;
}

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Users retrieved successfully',
    'data' => [
        'users' => $users,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'filters' => [
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order
        ],
        'role_counts' => $roleCounts,
        'status_counts' => $statusCounts,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>