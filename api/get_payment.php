<?php
// ============================================================
// CIBIL REPAIR CRM - Get Payment API
// Endpoint: /api/get_payment.php
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

// Database connection
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

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Get parameters
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$transaction_id = isset($_GET['transaction_id']) ? trim($_GET['transaction_id']) : '';
$order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// Check if table exists, if not create it
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    $createTable = "
        CREATE TABLE payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id VARCHAR(100) UNIQUE,
            order_id VARCHAR(100) UNIQUE,
            amount DECIMAL(10,2) DEFAULT 0,
            payment_method VARCHAR(50),
            status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
            case_id INT DEFAULT NULL,
            payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_transaction (transaction_id),
            INDEX idx_order (order_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    mysqli_query($conn, $createTable);
    
    // Insert sample payments
    $sampleSql = "
        INSERT INTO payments (transaction_id, order_id, amount, payment_method, status, case_id) VALUES
        ('TXN100001', 'ORD100001', 999.00, 'UPI', 'completed', 1),
        ('TXN100002', 'ORD100002', 1499.00, 'Credit Card', 'pending', 2),
        ('TXN100003', 'ORD100003', 499.00, 'Debit Card', 'completed', 3)
    ";
    mysqli_query($conn, $sampleSql);
}

// Build query conditions
$where = [];
$params = [];
$types = '';

if ($id > 0) {
    $where[] = "id = ?";
    $params[] = $id;
    $types .= 'i';
}

if (!empty($transaction_id)) {
    $where[] = "transaction_id = ?";
    $params[] = $transaction_id;
    $types .= 's';
}

if (!empty($order_id)) {
    $where[] = "order_id = ?";
    $params[] = $order_id;
    $types .= 's';
}

if ($case_id > 0) {
    $where[] = "case_id = ?";
    $params[] = $case_id;
    $types .= 'i';
}

if (!empty($status) && $status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM payments $whereClause";
$stmt = mysqli_prepare($conn, $countSql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$countResult = mysqli_stmt_get_result($stmt);
$totalRow = mysqli_fetch_assoc($countResult);
$total = $totalRow ? intval($totalRow['total']) : 0;
mysqli_stmt_close($stmt);

// Get payments
$sql = "SELECT * FROM payments $whereClause ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$payments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $payments[] = [
        'id' => intval($row['id']),
        'transaction_id' => $row['transaction_id'],
        'order_id' => $row['order_id'],
        'amount' => floatval($row['amount']),
        'payment_method' => $row['payment_method'],
        'status' => $row['status'],
        'case_id' => isset($row['case_id']) ? intval($row['case_id']) : null,
        'payment_date' => $row['payment_date'] ?? $row['created_at']
    ];
}
mysqli_stmt_close($stmt);

// Get status counts
$statusCounts = [];
$statuses = ['pending', 'completed', 'failed', 'refunded'];
foreach ($statuses as $s) {
    $sSql = "SELECT COUNT(*) as count FROM payments WHERE status = ?";
    $sStmt = mysqli_prepare($conn, $sSql);
    mysqli_stmt_bind_param($sStmt, 's', $s);
    mysqli_stmt_execute($sStmt);
    $sResult = mysqli_stmt_get_result($sStmt);
    $sRow = mysqli_fetch_assoc($sResult);
    $statusCounts[$s] = $sRow ? intval($sRow['count']) : 0;
    mysqli_stmt_close($sStmt);
}
$statusCounts['total'] = $total;

// Response
echo json_encode([
    'success' => true,
    'data' => [
        'payments' => $payments,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'status_counts' => $statusCounts,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>