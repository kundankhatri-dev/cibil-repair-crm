<?php
// ============================================================
// CIBIL REPAIR CRM - Get Single Customer Request API
// Endpoint: /api/get_request.php
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

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'customer_requests'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Customer requests table not found']);
    exit;
}

// ============================================================
// GET PARAMETERS
// ============================================================

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Request ID is required']);
    exit;
}

// ============================================================
// GET REQUEST
// ============================================================

$sql = "SELECT * FROM customer_requests WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$request = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$request) {
    echo json_encode(['success' => false, 'error' => 'Request not found']);
    exit;
}

// ============================================================
// GET ASSIGNED USER DETAILS
// ============================================================

$assignedUser = null;
if (!empty($request['assigned_to'])) {
    $uSql = "SELECT id, name, email, role FROM users WHERE id = ?";
    $uStmt = mysqli_prepare($conn, $uSql);
    mysqli_stmt_bind_param($uStmt, 'i', $request['assigned_to']);
    mysqli_stmt_execute($uStmt);
    $uResult = mysqli_stmt_get_result($uStmt);
    $assignedUser = mysqli_fetch_assoc($uResult);
    mysqli_stmt_close($uStmt);
}

// ============================================================
// GET CUSTOMER DETAILS
// ============================================================

$customer = null;
if (!empty($request['customer_id'])) {
    $cSql = "SELECT id, name, email, phone, city, address, status FROM customers WHERE id = ?";
    $cStmt = mysqli_prepare($conn, $cSql);
    mysqli_stmt_bind_param($cStmt, 'i', $request['customer_id']);
    mysqli_stmt_execute($cStmt);
    $cResult = mysqli_stmt_get_result($cStmt);
    $customer = mysqli_fetch_assoc($cResult);
    mysqli_stmt_close($cStmt);
} elseif (!empty($request['email'])) {
    $cSql = "SELECT id, name, email, phone, city, status FROM customers WHERE email = ?";
    $cStmt = mysqli_prepare($conn, $cSql);
    mysqli_stmt_bind_param($cStmt, 's', $request['email']);
    mysqli_stmt_execute($cStmt);
    $cResult = mysqli_stmt_get_result($cStmt);
    $customer = mysqli_fetch_assoc($cResult);
    mysqli_stmt_close($cStmt);
}

// ============================================================
// GET RELATED REQUESTS
// ============================================================

$relatedRequests = [];
if ($customer) {
    $rSql = "SELECT id, name, service, date, status, priority, created_at 
             FROM customer_requests 
             WHERE customer_id = ? AND id != ? 
             ORDER BY created_at DESC 
             LIMIT 5";
    $rStmt = mysqli_prepare($conn, $rSql);
    mysqli_stmt_bind_param($rStmt, 'ii', $customer['id'], $id);
    mysqli_stmt_execute($rStmt);
    $rResult = mysqli_stmt_get_result($rStmt);
    while ($row = mysqli_fetch_assoc($rResult)) {
        $relatedRequests[] = $row;
    }
    mysqli_stmt_close($rStmt);
} elseif (!empty($request['email'])) {
    $rSql = "SELECT id, name, service, date, status, priority, created_at 
             FROM customer_requests 
             WHERE email = ? AND id != ? 
             ORDER BY created_at DESC 
             LIMIT 5";
    $rStmt = mysqli_prepare($conn, $rSql);
    mysqli_stmt_bind_param($rStmt, 'si', $request['email'], $id);
    mysqli_stmt_execute($rStmt);
    $rResult = mysqli_stmt_get_result($rStmt);
    while ($row = mysqli_fetch_assoc($rResult)) {
        $relatedRequests[] = $row;
    }
    mysqli_stmt_close($rStmt);
}

// ============================================================
// FORMAT RESPONSE
// ============================================================

$statusConfig = [
    'pending' => ['label' => 'Pending', 'color' => 'warning'],
    'approved' => ['label' => 'Approved', 'color' => 'info'],
    'rejected' => ['label' => 'Rejected', 'color' => 'danger'],
    'in_progress' => ['label' => 'In Progress', 'color' => 'primary'],
    'completed' => ['label' => 'Completed', 'color' => 'success']
];

$priorityConfig = [
    'low' => ['label' => 'Low', 'color' => 'secondary'],
    'medium' => ['label' => 'Medium', 'color' => 'info'],
    'high' => ['label' => 'High', 'color' => 'warning'],
    'urgent' => ['label' => 'Urgent', 'color' => 'danger']
];

echo json_encode([
    'success' => true,
    'message' => 'Request retrieved successfully',
    'data' => [
        'id' => intval($request['id']),
        'name' => $request['name'],
        'email' => $request['email'] ?? '',
        'phone' => $request['phone'] ?? '',
        'service' => $request['service'] ?? 'Written Off',
        'date' => $request['date'] ?? null,
        'status' => $request['status'] ?? 'pending',
        'status_config' => $statusConfig[$request['status'] ?? 'pending'] ?? $statusConfig['pending'],
        'priority' => $request['priority'] ?? 'medium',
        'priority_config' => $priorityConfig[$request['priority'] ?? 'medium'] ?? $priorityConfig['medium'],
        'notes' => $request['notes'] ?? '',
        'assigned_to' => isset($request['assigned_to']) ? intval($request['assigned_to']) : null,
        'assigned_user' => $assignedUser,
        'customer_id' => isset($request['customer_id']) ? intval($request['customer_id']) : null,
        'customer' => $customer,
        'follow_up_date' => $request['follow_up_date'] ?? null,
        'created_at' => $request['created_at'] ?? null,
        'updated_at' => $request['updated_at'] ?? null,
        'related_requests' => $relatedRequests,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>