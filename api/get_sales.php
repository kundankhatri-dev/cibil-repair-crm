<?php
// ============================================================
// CIBIL REPAIR CRM - Get Sales API
// Endpoint: /api/get_sales.php
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

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'sales'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Sales table not found']);
    exit;
}

// ============================================================
// GET PARAMETERS
// ============================================================

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$service = isset($_GET['service']) ? trim($_GET['service']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'id';
$sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'DESC';

// ============================================================
# GET SINGLE SALE
// ============================================================

if ($id > 0) {
    $sql = "SELECT * FROM sales WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $sale = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$sale) {
        echo json_encode(['success' => false, 'error' => 'Sale not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $sale
    ]);
    mysqli_close($conn);
    exit;
}

// ============================================================
# BUILD QUERY WITH FILTERS
// ============================================================

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(customer_name LIKE ? OR customer_email LIKE ? OR customer_phone LIKE ? OR service LIKE ?)";
    $searchWild = "%$search%";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
    $types .= 'ssss';
}

if (!empty($status) && $status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($service) && $service !== 'all') {
    $where[] = "service = ?";
    $params[] = $service;
    $types .= 's';
}

if (!empty($from_date)) {
    $where[] = "DATE(sale_date) >= ?";
    $params[] = $from_date;
    $types .= 's';
}

if (!empty($to_date)) {
    $where[] = "DATE(sale_date) <= ?";
    $params[] = $to_date;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ============================================================
# GET TOTAL COUNT
// ============================================================

$countQuery = "SELECT COUNT(*) as total FROM sales $whereClause";
$stmt = mysqli_prepare($conn, $countQuery);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$countResult = mysqli_stmt_get_result($stmt);
$totalRow = mysqli_fetch_assoc($countResult);
$total = $totalRow ? intval($totalRow['total']) : 0;
mysqli_stmt_close($stmt);

// ============================================================
# GET SALES
// ============================================================

$query = "SELECT * FROM sales $whereClause ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$sales = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sales[] = [
        'id' => intval($row['id']),
        'customer_name' => $row['customer_name'] ?? '',
        'customer_email' => $row['customer_email'] ?? '',
        'customer_phone' => $row['customer_phone'] ?? '',
        'service' => $row['service'] ?? 'Written Off',
        'amount' => floatval($row['amount'] ?? 0),
        'commission_amount' => floatval($row['commission_amount'] ?? 0),
        'status' => $row['status'] ?? 'Pending',
        'sale_date' => $row['sale_date'] ?? null,
        'notes' => $row['notes'] ?? '',
        'created_at' => $row['created_at'] ?? null
    ];
}
mysqli_stmt_close($stmt);

// ============================================================
# GET STATUS COUNTS
// ============================================================

$statusCounts = ['Completed' => 0, 'Pending' => 0, 'Cancelled' => 0];
$statuses = ['Completed', 'Pending', 'Cancelled'];
foreach ($statuses as $s) {
    $sResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM sales WHERE status = '$s'");
    $sRow = mysqli_fetch_assoc($sResult);
    $statusCounts[$s] = $sRow ? intval($sRow['count']) : 0;
}
$statusCounts['total'] = $total;

// ============================================================
# GET SERVICE COUNTS
// ============================================================

$serviceCounts = [];
$sResult = mysqli_query($conn, "SELECT service, COUNT(*) as count FROM sales GROUP BY service");
while ($row = mysqli_fetch_assoc($sResult)) {
    $serviceCounts[$row['service']] = intval($row['count']);
}

// ============================================================
# GET SUMMARY STATS
// ============================================================

$stats = [
    'total_revenue' => 0,
    'pending_amount' => 0,
    'total_commission' => 0,
    'average_sale_value' => 0,
    'total_sales' => $total
];

$revenueResult = mysqli_query($conn, "SELECT IFNULL(SUM(amount), 0) as total FROM sales WHERE status = 'Completed'");
$revenueRow = mysqli_fetch_assoc($revenueResult);
$stats['total_revenue'] = floatval($revenueRow['total'] ?? 0);

$pendingResult = mysqli_query($conn, "SELECT IFNULL(SUM(amount), 0) as total FROM sales WHERE status = 'Pending'");
$pendingRow = mysqli_fetch_assoc($pendingResult);
$stats['pending_amount'] = floatval($pendingRow['total'] ?? 0);

$commissionResult = mysqli_query($conn, "SELECT IFNULL(SUM(commission_amount), 0) as total FROM sales WHERE status = 'Completed'");
$commissionRow = mysqli_fetch_assoc($commissionResult);
$stats['total_commission'] = floatval($commissionRow['total'] ?? 0);

$avgResult = mysqli_query($conn, "SELECT IFNULL(AVG(amount), 0) as avg FROM sales WHERE status = 'Completed'");
$avgRow = mysqli_fetch_assoc($avgResult);
$stats['average_sale_value'] = floatval($avgRow['avg'] ?? 0);

// ============================================================
# RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Sales retrieved successfully',
    'data' => [
        'sales' => $sales,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'search' => $search,
        'status' => $status,
        'service' => $service,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'sort_by' => $sort_by,
        'sort_order' => $sort_order,
        'status_counts' => $statusCounts,
        'service_counts' => $serviceCounts,
        'stats' => $stats,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>