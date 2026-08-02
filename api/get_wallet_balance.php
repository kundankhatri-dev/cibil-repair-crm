<?php
// ============================================================
// CIBIL REPAIR CRM - Get Wallet Balance API (ENHANCED)
// Endpoint: /api/get_wallet_balance.php
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

$allowedRoles = ['admin', 'super_admin', 'manager', 'partner'];
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
// GET PARAMETERS
// ============================================================

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;

// Validate
if ($limit < 1 || $limit > 50) $limit = 10;
if ($days < 1 || $days > 365) $days = 30;

// ============================================================
// CREATE TABLES IF NOT EXISTS
// ============================================================

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

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    wallet_id INT NOT NULL,
    date DATE DEFAULT CURRENT_DATE,
    description VARCHAR(255),
    amount DECIMAL(15,2) NOT NULL,
    type ENUM('credit','debit') NOT NULL,
    method VARCHAR(50) DEFAULT 'Cash',
    fee_amount DECIMAL(15,2) DEFAULT 0.00,
    gst_amount DECIMAL(15,2) DEFAULT 0.00,
    total_amount DECIMAL(15,2) DEFAULT 0.00,
    balance_after DECIMAL(15,2) DEFAULT 0.00,
    reference_id VARCHAR(100),
    status ENUM('pending','completed','failed','cancelled') DEFAULT 'completed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wallet_id (wallet_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ============================================================
// GET WALLET
// ============================================================

$walletQuery = "SELECT id, balance, updated_at FROM wallet WHERE user_id = ?";
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
    $lastUpdated = date('Y-m-d H:i:s');
} else {
    $walletId = (int)$wallet['id'];
    $balance = (float)$wallet['balance'];
    $lastUpdated = $wallet['updated_at'] ?? date('Y-m-d H:i:s');
}

// ============================================================
// GET TRANSACTION STATISTICS
// ============================================================

// Today's transactions
$todayQuery = "
    SELECT 
        type,
        IFNULL(SUM(amount), 0) as total,
        COUNT(*) as count
    FROM transactions 
    WHERE wallet_id = ? AND DATE(created_at) = CURDATE()
    GROUP BY type
";

$stmt = mysqli_prepare($conn, $todayQuery);
mysqli_stmt_bind_param($stmt, "i", $walletId);
mysqli_stmt_execute($stmt);
$todayResult = mysqli_stmt_get_result($stmt);

$todayCredits = 0;
$todayDebits = 0;
$todayCount = 0;
while ($row = mysqli_fetch_assoc($todayResult)) {
    if ($row['type'] === 'credit') {
        $todayCredits = (float)$row['total'];
    } elseif ($row['type'] === 'debit') {
        $todayDebits = (float)$row['total'];
    }
    $todayCount += (int)$row['count'];
}
mysqli_stmt_close($stmt);

// This month's transactions
$monthQuery = "
    SELECT 
        type,
        IFNULL(SUM(amount), 0) as total,
        COUNT(*) as count
    FROM transactions 
    WHERE wallet_id = ? 
    AND MONTH(created_at) = MONTH(CURDATE()) 
    AND YEAR(created_at) = YEAR(CURDATE())
    GROUP BY type
";

$stmt = mysqli_prepare($conn, $monthQuery);
mysqli_stmt_bind_param($stmt, "i", $walletId);
mysqli_stmt_execute($stmt);
$monthResult = mysqli_stmt_get_result($stmt);

$monthCredits = 0;
$monthDebits = 0;
$monthCount = 0;
while ($row = mysqli_fetch_assoc($monthResult)) {
    if ($row['type'] === 'credit') {
        $monthCredits = (float)$row['total'];
    } elseif ($row['type'] === 'debit') {
        $monthDebits = (float)$row['total'];
    }
    $monthCount += (int)$row['count'];
}
mysqli_stmt_close($stmt);

// Total transactions count
$totalQuery = "SELECT COUNT(*) as count FROM transactions WHERE wallet_id = ?";
$stmt = mysqli_prepare($conn, $totalQuery);
mysqli_stmt_bind_param($stmt, "i", $walletId);
mysqli_stmt_execute($stmt);
$totalResult = mysqli_stmt_get_result($stmt);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalCount = $totalRow ? (int)$totalRow['count'] : 0;
mysqli_stmt_close($stmt);

// Recent transactions
$recentQuery = "
    SELECT 
        id,
        date,
        description,
        amount,
        type,
        method,
        fee_amount,
        gst_amount,
        total_amount,
        balance_after,
        reference_id,
        status,
        created_at
    FROM transactions 
    WHERE wallet_id = ?
    ORDER BY created_at DESC 
    LIMIT ?
";

$stmt = mysqli_prepare($conn, $recentQuery);
mysqli_stmt_bind_param($stmt, "ii", $walletId, $limit);
mysqli_stmt_execute($stmt);
$recentResult = mysqli_stmt_get_result($stmt);

$recentTransactions = [];
while ($row = mysqli_fetch_assoc($recentResult)) {
    $recentTransactions[] = [
        'id' => (int)$row['id'],
        'date' => $row['date'] ?? date('Y-m-d'),
        'description' => $row['description'] ?? 'Transaction',
        'amount' => (float)$row['amount'],
        'type' => $row['type'] ?? 'debit',
        'method' => $row['method'] ?? 'Cash',
        'fee_amount' => (float)($row['fee_amount'] ?? 0),
        'gst_amount' => (float)($row['gst_amount'] ?? 0),
        'total_amount' => (float)($row['total_amount'] ?? $row['amount']),
        'balance_after' => (float)($row['balance_after'] ?? 0),
        'reference_id' => $row['reference_id'] ?? '',
        'status' => $row['status'] ?? 'completed',
        'created_at' => $row['created_at']
    ];
}
mysqli_stmt_close($stmt);

// ============================================================
// DAILY BREAKDOWN (Last 7 days)
// ============================================================

$dailyQuery = "
    SELECT 
        DATE(created_at) as date,
        SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as credits,
        SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as debits,
        COUNT(*) as count
    FROM transactions 
    WHERE wallet_id = ? 
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) DESC
";

$stmt = mysqli_prepare($conn, $dailyQuery);
mysqli_stmt_bind_param($stmt, "i", $walletId);
mysqli_stmt_execute($stmt);
$dailyResult = mysqli_stmt_get_result($stmt);

$dailyBreakdown = [];
while ($row = mysqli_fetch_assoc($dailyResult)) {
    $dailyBreakdown[] = [
        'date' => $row['date'],
        'credits' => (float)$row['credits'],
        'debits' => (float)$row['debits'],
        'net' => (float)$row['credits'] - (float)$row['debits'],
        'count' => (int)$row['count']
    ];
}
mysqli_stmt_close($stmt);

// ============================================================
// GET WALLET LIMITS
// ============================================================

$limits = [
    'minimum_balance' => 0,
    'daily_limit' => 50000,
    'monthly_limit' => 500000,
    'max_deposit' => 100000,
    'max_withdrawal' => 50000,
    'min_withdrawal' => 100
];

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'data' => [
        'wallet_id' => $walletId,
        'balance' => $balance,
        'formatted_balance' => '₹' . number_format($balance, 2),
        'last_updated' => $lastUpdated,
        'limits' => $limits,
        'stats' => [
            'today' => [
                'credits' => $todayCredits,
                'debits' => $todayDebits,
                'net' => $todayCredits - $todayDebits,
                'transactions' => $todayCount
            ],
            'this_month' => [
                'credits' => $monthCredits,
                'debits' => $monthDebits,
                'net' => $monthCredits - $monthDebits,
                'transactions' => $monthCount
            ],
            'total_transactions' => $totalCount,
            'daily_breakdown' => $dailyBreakdown
        ],
        'recent_transactions' => $recentTransactions,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

// ============================================================
// CLEANUP
// ============================================================

mysqli_close($conn);
exit;
?>