<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

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

$period = isset($_GET['period']) ? trim($_GET['period']) : 'monthly';
$dateColumn = 'sale_date';

$now = new DateTime();
$start = clone $now;

// Fix: Use proper date modifications
switch ($period) {
    case 'daily':
        $start->modify('-30 days');
        break;
    case 'weekly':
        $start->modify('-12 weeks');
        break;
    case 'monthly':
        $start->modify('-12 months');
        break;
    case 'quarterly':
        $start->modify('-24 months'); // 8 quarters = 24 months
        break;
    case 'yearly':
        $start->modify('-5 years');
        break;
    default:
        $start->modify('-12 months');
}

$startDate = $start->format('Y-m-d');
$endDate = $now->format('Y-m-d');

// Summary
$summarySql = "SELECT COUNT(*) as total_transactions, SUM(amount) as total_revenue, AVG(amount) as avg_amount FROM sales WHERE $dateColumn BETWEEN '$startDate' AND '$endDate'";
$summaryResult = mysqli_query($conn, $summarySql);
$summary = mysqli_fetch_assoc($summaryResult);

if (!$summary) {
    $summary = ['total_transactions' => 0, 'total_revenue' => 0, 'avg_amount' => 0];
}

// Period data - simplified for all periods
if ($period === 'quarterly') {
    $periodSql = "SELECT CONCAT(YEAR($dateColumn), '-Q', QUARTER($dateColumn)) as period, COUNT(*) as transactions, SUM(amount) as revenue FROM sales WHERE $dateColumn BETWEEN '$startDate' AND '$endDate' GROUP BY YEAR($dateColumn), QUARTER($dateColumn) ORDER BY YEAR($dateColumn) ASC, QUARTER($dateColumn) ASC";
} elseif ($period === 'daily') {
    $periodSql = "SELECT DATE($dateColumn) as period, COUNT(*) as transactions, SUM(amount) as revenue FROM sales WHERE $dateColumn BETWEEN '$startDate' AND '$endDate' GROUP BY DATE($dateColumn) ORDER BY period ASC LIMIT 30";
} elseif ($period === 'weekly') {
    $periodSql = "SELECT CONCAT(YEAR($dateColumn), '-W', WEEK($dateColumn)) as period, COUNT(*) as transactions, SUM(amount) as revenue FROM sales WHERE $dateColumn BETWEEN '$startDate' AND '$endDate' GROUP BY YEAR($dateColumn), WEEK($dateColumn) ORDER BY YEAR($dateColumn) ASC, WEEK($dateColumn) ASC";
} elseif ($period === 'monthly') {
    $periodSql = "SELECT DATE_FORMAT($dateColumn, '%Y-%m') as period, COUNT(*) as transactions, SUM(amount) as revenue FROM sales WHERE $dateColumn BETWEEN '$startDate' AND '$endDate' GROUP BY DATE_FORMAT($dateColumn, '%Y-%m') ORDER BY period ASC";
} elseif ($period === 'yearly') {
    $periodSql = "SELECT YEAR($dateColumn) as period, COUNT(*) as transactions, SUM(amount) as revenue FROM sales WHERE $dateColumn BETWEEN '$startDate' AND '$endDate' GROUP BY YEAR($dateColumn) ORDER BY period ASC";
} else {
    $periodSql = "SELECT DATE_FORMAT($dateColumn, '%Y-%m') as period, COUNT(*) as transactions, SUM(amount) as revenue FROM sales WHERE $dateColumn BETWEEN '$startDate' AND '$endDate' GROUP BY DATE_FORMAT($dateColumn, '%Y-%m') ORDER BY period ASC";
}

$periodResult = mysqli_query($conn, $periodSql);

// Check if query failed
if (!$periodResult) {
    echo json_encode([
        'success' => false,
        'error' => 'Query failed: ' . mysqli_error($conn),
        'sql' => $periodSql
    ]);
    exit;
}

$periodData = [];
while ($row = mysqli_fetch_assoc($periodResult)) {
    $periodData[] = $row;
}

// Service data
$serviceSql = "SELECT service, COUNT(*) as transactions, SUM(amount) as revenue FROM sales WHERE $dateColumn BETWEEN '$startDate' AND '$endDate' GROUP BY service ORDER BY revenue DESC";
$serviceResult = mysqli_query($conn, $serviceSql);
$serviceData = [];
while ($row = mysqli_fetch_assoc($serviceResult)) {
    $serviceData[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => [
        'period' => $period,
        'date_range' => ['start' => $startDate, 'end' => $endDate],
        'summary' => [
            'total_transactions' => intval($summary['total_transactions'] ?? 0),
            'total_revenue' => floatval($summary['total_revenue'] ?? 0),
            'avg_amount' => floatval($summary['avg_amount'] ?? 0)
        ],
        'period_data' => $periodData,
        'service_data' => $serviceData,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>