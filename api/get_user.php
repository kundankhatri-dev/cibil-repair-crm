<?php
// ============================================================
// CIBIL REPAIR CRM - Get Single User API
// Endpoint: /api/get_user.php
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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit;
}

// ============================================================
// GET USER
// ============================================================

$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// ============================================================
// GET USER STATS
// ============================================================

// Total activities
$actResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM activity_logs WHERE user_id = $id");
$actRow = mysqli_fetch_assoc($actResult);
$totalActivities = $actRow ? intval($actRow['count']) : 0;

// Last activity
$lastActResult = mysqli_query($conn, "SELECT created_at FROM activity_logs WHERE user_id = $id ORDER BY created_at DESC LIMIT 1");
$lastActRow = mysqli_fetch_assoc($lastActResult);
$lastActivity = $lastActRow ? $lastActRow['created_at'] : null;

// Recent activities
$activities = [];
$actSql = "SELECT id, user_name, action, details, ip_address, created_at FROM activity_logs WHERE user_id = $id ORDER BY created_at DESC LIMIT 10";
$actResult = mysqli_query($conn, $actSql);
while ($row = mysqli_fetch_assoc($actResult)) {
    $activities[] = $row;
}

// Login history
$loginHistory = [];
$loginSql = "SELECT id, user_name, action, ip_address, created_at FROM activity_logs WHERE user_id = $id AND (action LIKE '%Login%' OR action LIKE '%Logout%') ORDER BY created_at DESC LIMIT 5";
$loginResult = mysqli_query($conn, $loginSql);
while ($row = mysqli_fetch_assoc($loginResult)) {
    $loginHistory[] = $row;
}

// Assigned requests
$requests = [];
$reqSql = "SELECT id, name, service, status, priority, created_at FROM customer_requests WHERE assigned_to = $id ORDER BY created_at DESC LIMIT 5";
$reqResult = mysqli_query($conn, $reqSql);
while ($row = mysqli_fetch_assoc($reqResult)) {
    $requests[] = $row;
}

// ============================================================
# CALCULATE ACCOUNT AGE
// ============================================================

$accountAge = 0;
if (!empty($user['created_at'])) {
    $created = new DateTime($user['created_at']);
    $now = new DateTime();
    $interval = $created->diff($now);
    $accountAge = $interval->days;
}

// ============================================================
# RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'User retrieved successfully',
    'data' => [
        'id' => intval($user['id']),
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? '',
        'role' => $user['role'] ?? 'client',
        'status' => $user['status'] ?? 'active',
        'city' => $user['city'] ?? '',
        'created_at' => $user['created_at'] ?? null,
        'updated_at' => $user['updated_at'] ?? null,
        'last_login' => $user['last_login'] ?? null,
        'last_login_ip' => $user['last_login_ip'] ?? '',
        'login_attempts' => intval($user['login_attempts'] ?? 0),
        'stats' => [
            'account_age' => $accountAge . ' days',
            'total_activities' => $totalActivities,
            'last_activity' => $lastActivity,
            'assigned_requests' => count($requests)
        ],
        'recent_activities' => $activities,
        'login_history' => $loginHistory,
        'assigned_requests' => $requests,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>