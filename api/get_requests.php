<?php
// ============================================================
// CIBIL REPAIR CRM - Get Customer Requests API
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'customer_requests'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Customer requests table not found']);
    exit;
}

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';

$where = [];
if (!empty($status) && $status !== 'all') {
    $where[] = "status = '$status'";
}
if (!empty($priority) && $priority !== 'all') {
    $where[] = "priority = '$priority'";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM customer_requests $whereClause");
$countRow = mysqli_fetch_assoc($countResult);
$total = $countRow ? intval($countRow['total']) : 0;

$query = "SELECT * FROM customer_requests $whereClause ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

$requests = [];
while ($row = mysqli_fetch_assoc($result)) {
    $requests[] = [
        'id' => intval($row['id']),
        'name' => $row['name'],
        'email' => $row['email'] ?? '',
        'phone' => $row['phone'] ?? '',
        'service' => $row['service'] ?? 'Written Off',
        'status' => $row['status'] ?? 'pending',
        'priority' => $row['priority'] ?? 'medium',
        'notes' => $row['notes'] ?? '',
        'created_at' => $row['created_at'] ?? null
    ];
}

$statusCounts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'in_progress' => 0, 'completed' => 0];
$statuses = ['pending', 'approved', 'rejected', 'in_progress', 'completed'];
foreach ($statuses as $s) {
    $sResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM customer_requests WHERE status = '$s'");
    $sRow = mysqli_fetch_assoc($sResult);
    $statusCounts[$s] = $sRow ? intval($sRow['count']) : 0;
}
$statusCounts['total'] = $total;

echo json_encode([
    'success' => true,
    'data' => [
        'requests' => $requests,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'status_counts' => $statusCounts,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>