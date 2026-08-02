<?php
// ============================================================
// CIBIL REPAIR CRM - Get Financial Summary API
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GET PARAMETERS
// ============================================================

$period = isset($_GET['period']) ? trim($_GET['period']) : 'monthly';
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Set default date range
if (empty($startDate) || empty($endDate)) {
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime('-30 days'));
}

// ============================================================
// GET REVENUE
// ============================================================

$revenueQuery = "SELECT 
    IFNULL(SUM(amount), 0) as total_revenue,
    COUNT(*) as transaction_count,
    IFNULL(AVG(amount), 0) as avg_transaction,
    IFNULL(MAX(amount), 0) as max_transaction,
    IFNULL(MIN(amount), 0) as min_transaction
FROM sales 
WHERE status = 'completed' 
AND sale_date BETWEEN '$startDate' AND '$endDate'";

$revenueResult = mysqli_query($conn, $revenueQuery);
$revenue = mysqli_fetch_assoc($revenueResult);

// ============================================================
// GET EXPENSES
// ============================================================

$expenseQuery = "SELECT 
    IFNULL(SUM(amount), 0) as total_expenses,
    COUNT(*) as expense_count
FROM expenses 
WHERE date BETWEEN '$startDate' AND '$endDate'";

$expenseResult = mysqli_query($conn, $expenseQuery);
$expenses = mysqli_fetch_assoc($expenseResult);

// ============================================================
// GET EXPENSES BY CATEGORY
// ============================================================

$categoryQuery = "SELECT 
    category, 
    IFNULL(SUM(amount), 0) as total,
    COUNT(*) as count
FROM expenses 
WHERE date BETWEEN '$startDate' AND '$endDate'
GROUP BY category
ORDER BY total DESC";

$categoryResult = mysqli_query($conn, $categoryQuery);
$categoryBreakdown = [];
while ($row = mysqli_fetch_assoc($categoryResult)) {
    $categoryBreakdown[] = $row;
}

// ============================================================
// GET WALLET BALANCE
// ============================================================

$walletQuery = "SELECT balance FROM wallet WHERE user_id = " . (int)$_SESSION['user_id'];
$walletResult = mysqli_query($conn, $walletQuery);
$wallet = mysqli_fetch_assoc($walletResult);
$walletBalance = $wallet ? (float)$wallet['balance'] : 0;

// ============================================================
// GET DAILY TREND
// ============================================================

$trendQuery = "SELECT 
    DATE(sale_date) as date,
    IFNULL(SUM(amount), 0) as revenue
FROM sales 
WHERE status = 'completed' 
AND sale_date BETWEEN '$startDate' AND '$endDate'
GROUP BY DATE(sale_date)
ORDER BY date ASC";

$trendResult = mysqli_query($conn, $trendQuery);
$dailyTrend = [];
while ($row = mysqli_fetch_assoc($trendResult)) {
    $dailyTrend[] = $row;
}

// ============================================================
// CALCULATE PROFIT/LOSS
// ============================================================

$totalRevenue = (float)($revenue['total_revenue'] ?? 0);
$totalExpenses = (float)($expenses['total_expenses'] ?? 0);
$netProfit = $totalRevenue - $totalExpenses;
$profitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0;

// ============================================================
// CALCULATE GROWTH
// ============================================================

// Previous period revenue
$prevStart = date('Y-m-d', strtotime($startDate . ' -30 days'));
$prevEnd = date('Y-m-d', strtotime($endDate . ' -30 days'));

$prevQuery = "SELECT IFNULL(SUM(amount), 0) as total FROM sales 
              WHERE status = 'completed' 
              AND sale_date BETWEEN '$prevStart' AND '$prevEnd'";
$prevResult = mysqli_query($conn, $prevQuery);
$prevRevenue = mysqli_fetch_assoc($prevResult);
$prevTotal = (float)($prevRevenue['total'] ?? 0);

$growth = $prevTotal > 0 ? round((($totalRevenue - $prevTotal) / $prevTotal) * 100, 2) : 0;

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'data' => [
        'period' => [
            'start' => $startDate,
            'end' => $endDate
        ],
        'summary' => [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'profit_margin' => $profitMargin,
            'transaction_count' => (int)($revenue['transaction_count'] ?? 0),
            'avg_transaction' => (float)($revenue['avg_transaction'] ?? 0),
            'wallet_balance' => $walletBalance,
            'growth_percentage' => $growth
        ],
        'expense_breakdown' => $categoryBreakdown,
        'daily_trend' => $dailyTrend
    ]
]);

mysqli_close($conn);
exit;
?>