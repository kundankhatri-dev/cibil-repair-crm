<?php
// ============================================================
// CIBIL REPAIR CRM - Get Transactions API
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

$userId = (int)$_SESSION['user_id'];

// ============================================================
// CREATE TABLES IF NOT EXISTS
// ============================================================

// Wallet table
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS wallet (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL DEFAULT 1,
    balance DECIMAL(15,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Transactions table
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    wallet_id INT NOT NULL,
    date DATE DEFAULT CURRENT_DATE,
    description VARCHAR(255),
    amount DECIMAL(15,2) NOT NULL,
    type ENUM('credit','debit') NOT NULL,
    method VARCHAR(50) DEFAULT 'Cash',
    balance_after DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('pending','completed','failed','cancelled') DEFAULT 'completed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wallet_id (wallet_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ============================================================
// GET WALLET ID
// ============================================================

$walletQuery = "SELECT id, balance FROM wallet WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $walletQuery);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$wallet = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// If no wallet, create one
if (!$wallet) {
    $insert = "INSERT INTO wallet (user_id, balance) VALUES (?, 0)";
    $stmt = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $walletId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    $balance = 0;
} else {
    $walletId = (int)$wallet['id'];
    $balance = (float)$wallet['balance'];
}

// ============================================================
// GET PARAMETERS
// ============================================================

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$method = isset($_GET['method']) ? trim($_GET['method']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Validate
if ($limit < 1) $limit = 10;
if ($limit > 500) $limit = 50;
if ($offset < 0) $offset = 0;

// ============================================================
// BUILD QUERY
// ============================================================

$where = [];
$params = [];
$types = '';

// Always filter by wallet_id
$where[] = "wallet_id = ?";
$params[] = $walletId;
$types .= 'i';

// Search filter
if (!empty($search)) {
    $searchParam = "%$search%";
    $where[] = "(description LIKE ? OR method LIKE ?)";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

// Type filter
if (!empty($type) && $type !== 'all') {
    $where[] = "type = ?";
    $params[] = $type;
    $types .= 's';
}

// Method filter
if (!empty($method) && $method !== 'all') {
    $where[] = "method = ?";
    $params[] = $method;
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
// GET TRANSACTIONS
// ============================================================

$query = "SELECT id, date, description, amount, type, method, balance_after, status, created_at 
          FROM transactions $whereClause
          ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$transactions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $transactions[] = [
        'id' => (int)$row['id'],
        'date' => $row['date'],
        'description' => $row['description'] ?? '',
        'amount' => (float)$row['amount'],
        'type' => $row['type'],
        'method' => $row['method'] ?? 'Cash',
        'balance_after' => (float)($row['balance_after'] ?? 0),
        'status' => $row['status'] ?? 'completed',
        'created_at' => $row['created_at']
    ];
}
mysqli_stmt_close($stmt);

// ============================================================
// GET TOTAL COUNT
// ============================================================

$countWhere = array_slice($where, 0, count($where) - 2);
$countClause = !empty($countWhere) ? 'WHERE ' . implode(' AND ', $countWhere) : '';

$countQuery = "SELECT COUNT(*) as total FROM transactions $countClause";
$countResult = mysqli_query($conn, $countQuery);
$total = $countResult ? (int)mysqli_fetch_assoc($countResult)['total'] : 0;

// ============================================================
// GET STATS
// ============================================================

$statsQuery = "SELECT 
    COUNT(*) as total_count,
    SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as total_credits,
    SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as total_debits
    FROM transactions WHERE wallet_id = ?";

$stmt = mysqli_prepare($conn, $statsQuery);
mysqli_stmt_bind_param($stmt, "i", $walletId);
mysqli_stmt_execute($stmt);
$statsResult = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($statsResult);
mysqli_stmt_close($stmt);

// ============================================================
// GET STATUS COUNTS
// ============================================================

$statusCounts = [];
$statuses = ['pending', 'completed', 'failed', 'cancelled'];
foreach ($statuses as $s) {
    $sResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM transactions WHERE wallet_id = $walletId AND status = '$s'");
    $sRow = mysqli_fetch_assoc($sResult);
    $statusCounts[$s] = $sRow ? intval($sRow['count']) : 0;
}
$statusCounts['total'] = $total;

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Transactions retrieved successfully',
    'data' => [
        'wallet_id' => $walletId,
        'balance' => $balance,
        'formatted_balance' => '₹' . number_format($balance, 2),
        'transactions' => $transactions,
        'stats' => [
            'total_count' => (int)($stats['total_count'] ?? 0),
            'total_credits' => (float)($stats['total_credits'] ?? 0),
            'total_debits' => (float)($stats['total_debits'] ?? 0),
            'net_balance' => (float)($stats['total_credits'] ?? 0) - (float)($stats['total_debits'] ?? 0)
        ],
        'status_counts' => $statusCounts,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => $total
        ],
        'filters' => [
            'search' => $search,
            'type' => $type,
            'method' => $method,
            'status' => $status
        ],
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>