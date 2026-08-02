<?php
// ============================================================
// CIBIL REPAIR CRM - Get Single Registration Code API
// Endpoint: /api/get_registration_code.php
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

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'registration_codes'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Registration codes table not found']);
    exit;
}

// ============================================================
// GET PARAMETERS
// ============================================================

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

if (!$id && empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Either ID or Code is required']);
    exit;
}

// ============================================================
// GET REGISTRATION CODE
// ============================================================

if ($id > 0) {
    $sql = "SELECT * FROM registration_codes WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $registrationCode = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} elseif (!empty($code)) {
    $sql = "SELECT * FROM registration_codes WHERE code = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $registrationCode = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$registrationCode) {
    echo json_encode(['success' => false, 'error' => 'Registration code not found']);
    exit;
}

// ============================================================
// GET USERS REGISTERED WITH THIS CODE
// ============================================================

$registeredUsers = [];
$userSql = "SELECT id, name, email, phone, role, status, created_at FROM users WHERE registration_code = ?";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, 's', $registrationCode['code']);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);
while ($row = mysqli_fetch_assoc($userResult)) {
    $registeredUsers[] = $row;
}
mysqli_stmt_close($userStmt);

// ============================================================
// GET CREATOR DETAILS
// ============================================================

$creator = null;
if (!empty($registrationCode['created_by'])) {
    $cSql = "SELECT id, name, email, role FROM users WHERE id = ?";
    $cStmt = mysqli_prepare($conn, $cSql);
    mysqli_stmt_bind_param($cStmt, 'i', $registrationCode['created_by']);
    mysqli_stmt_execute($cStmt);
    $cResult = mysqli_stmt_get_result($cStmt);
    $creator = mysqli_fetch_assoc($cResult);
    mysqli_stmt_close($cStmt);
}

// ============================================================
// CALCULATE STATS
// ============================================================

$isExpired = strtotime($registrationCode['expires_at']) < strtotime(date('Y-m-d H:i:s'));

$status = 'active';
if ($registrationCode['is_used'] == 1) {
    $status = 'used';
} elseif ($isExpired) {
    $status = 'expired';
}

// Total codes
$totalResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM registration_codes");
$totalRow = mysqli_fetch_assoc($totalResult);
$totalCodes = $totalRow ? intval($totalRow['count']) : 1;

// Used codes
$usedResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM registration_codes WHERE is_used = 1");
$usedRow = mysqli_fetch_assoc($usedResult);
$usedCodes = $usedRow ? intval($usedRow['count']) : 0;

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Registration code retrieved successfully',
    'data' => [
        'id' => intval($registrationCode['id']),
        'code' => $registrationCode['code'],
        'role' => $registrationCode['role'] ?? 'client',
        'assigned_to_email' => $registrationCode['assigned_to_email'] ?? '',
        'expiry_days' => intval($registrationCode['expiry_days'] ?? 30),
        'expires_at' => $registrationCode['expires_at'],
        'is_used' => (bool)$registrationCode['is_used'],
        'used_at' => $registrationCode['used_at'] ?? null,
        'created_by' => $registrationCode['created_by'] ? intval($registrationCode['created_by']) : null,
        'creator' => $creator,
        'notes' => $registrationCode['notes'] ?? '',
        'is_expired' => $isExpired,
        'status' => $status,
        'created_at' => $registrationCode['created_at'],
        'stats' => [
            'registered_users' => count($registeredUsers),
            'total_codes' => $totalCodes,
            'used_codes' => $usedCodes,
            'usage_rate' => $totalCodes > 0 ? round(($usedCodes / $totalCodes) * 100, 1) : 0
        ],
        'registered_users' => $registeredUsers,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>