<?php
// ============================================================
// CIBIL REPAIR CRM - Get Revenue Report (SIMPLIFIED)
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

$period = isset($_GET['period']) ? trim($_GET['period']) : 'monthly';
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Set date range
$now = new DateTime();
switch ($period) {
    case 'daily': $start = clone $now; $start->modify('-30 days'); break;
    case 'weekly': $start = clone $now; $start->modify('-12 weeks'); break;
    case 'monthly': $start = clone $now; $start->modify('-12 months'); break;
    case 'quarterly': $start = clone $now; $start->modify('-8 quarters'); break;
    case 'yearly': $start = clone $now; $start->modify('-5 years'); break;
    default: $start = clone $now; $start->modify('-12 months');
}

$startDate = $start->format('Y-m-d');
$endDate = $now->format('Y-m-d');

// Get revenue summary
$summarySql = "
    SELECT 
        COUNT(*) as total_transactions,
        SUM(amount) as total_revenue,
        AVG(amount) as average_amount,
        MAX(amount) as max_amount,
        MIN(amount) as min_amount
    FROM sales
    WHERE date BETWEEN '$startDate' AND '$endDate'
";

$summaryResult = mysqli_query($conn, $summarySql);
$summary = mysqli_fetch_assoc($summaryResult);

if (!$summary) {
    $summary = [
        'total_transactions' => 0,
        'total_revenue' => 0,
        'average_amount' => 0,
        'max_amount' => 0,
        'min_amount' => 0
    ];
}

// Get revenue by period
$periodSql = "
    SELECT 
        DATE_FORMAT(date, '%Y-%m') as period,
        COUNT(*) as transactions,
        SUM(amount) as revenue
    FROM sales
    WHERE date BETWEEN '$startDate' AND '$endDate'
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY period ASC
";

$periodResult = mysqli_query($conn, $periodSql);
$periodData = [];
while ($row = mysqli_fetch_assoc($periodResult)) {
    $periodData[] = $row;
}

// Get revenue by service
$serviceSql = "
    SELECT 
        service as service_type,
        COUNT(*) as transactions,
        SUM(amount) as revenue
    FROM sales
    WHERE date BETWEEN '$startDate' AND '$endDate'
    GROUP BY service
    ORDER BY revenue DESC
";

$serviceResult = mysqli_query($conn, $serviceSql);
$serviceData = [];
while ($row = mysqli_fetch_assoc($serviceResult)) {
    $serviceData[] = $row;
}

echo json_encode([
    'success' => true,
    'message' => 'Revenue report generated successfully',
    'data' => [
        'period' => $period,
        'date_range' => [
            'start' => $startDate,
            'end' => $endDate
        ],
        'summary' => [
            'total_transactions' => intval($summary['total_transactions'] ?? 0),
            'total_revenue' => floatval($summary['total_revenue'] ?? 0),
            'average_amount' => floatval($summary['average_amount'] ?? 0),
            'max_amount' => floatval($summary['max_amount'] ?? 0),
            'min_amount' => floatval($summary['min_amount'] ?? 0)
        ],
        'period_data' => $periodData,
        'service_data' => $serviceData,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>