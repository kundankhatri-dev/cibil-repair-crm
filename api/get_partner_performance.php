<?php
// ============================================================
// CIBIL REPAIR CRM - Get Partner Performance (MINIMAL)
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

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
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
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
$partnerId = isset($_GET['partner_id']) ? intval($_GET['partner_id']) : 0;
$period = isset($_GET['period']) ? trim($_GET['period']) : 'monthly';

// Set date range
$now = new DateTime();
$start = clone $now;

switch ($period) {
    case 'daily': $start->modify('-30 days'); break;
    case 'weekly': $start->modify('-12 weeks'); break;
    case 'monthly': $start->modify('-12 months'); break;
    case 'quarterly': $start->modify('-8 quarters'); break;
    case 'yearly': $start->modify('-5 years'); break;
    default: $start->modify('-12 months');
}

$startDate = $start->format('Y-m-d');
$endDate = $now->format('Y-m-d');

// Simple query to get partners with sales data
$sql = "
    SELECT 
        p.id,
        p.name,
        p.email,
        p.phone,
        p.location,
        p.owner,
        p.status,
        p.commission_rate,
        COUNT(s.id) as total_sales,
        SUM(s.amount) as total_revenue
    FROM partners p
    LEFT JOIN sales s ON s.partner_id = p.id AND s.sale_date BETWEEN '$startDate' AND '$endDate'
    GROUP BY p.id
    ORDER BY total_revenue DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Query error: ' . mysqli_error($conn)]);
    exit;
}

$partners = [];
while ($row = mysqli_fetch_assoc($result)) {
    $partners[] = [
        'id' => intval($row['id']),
        'name' => $row['name'] ?? 'Unknown',
        'email' => $row['email'] ?? '',
        'phone' => $row['phone'] ?? '',
        'location' => $row['location'] ?? '',
        'owner' => $row['owner'] ?? '',
        'status' => $row['status'] ?? 'active',
        'commission_rate' => intval($row['commission_rate'] ?? 10),
        'total_sales' => intval($row['total_sales'] ?? 0),
        'total_revenue' => floatval($row['total_revenue'] ?? 0)
    ];
}

mysqli_free_result($result);

// Get aggregate stats
$aggSql = "
    SELECT 
        SUM(s.amount) as total_revenue,
        COUNT(s.id) as total_transactions,
        COUNT(DISTINCT s.partner_id) as active_partners
    FROM sales s
    WHERE s.sale_date BETWEEN '$startDate' AND '$endDate'
";

$aggResult = mysqli_query($conn, $aggSql);
$aggregates = mysqli_fetch_assoc($aggResult);

if (!$aggregates) {
    $aggregates = ['total_revenue' => 0, 'total_transactions' => 0, 'active_partners' => 0];
}

// Response
echo json_encode([
    'success' => true,
    'message' => 'Partner performance report generated',
    'data' => [
        'period' => $period,
        'date_range' => ['start' => $startDate, 'end' => $endDate],
        'aggregates' => [
            'total_revenue' => floatval($aggregates['total_revenue'] ?? 0),
            'total_transactions' => intval($aggregates['total_transactions'] ?? 0),
            'active_partners' => intval($aggregates['active_partners'] ?? 0)
        ],
        'partners' => $partners,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>