<?php
// ============================================================
// CIBIL REPAIR CRM - Lead Conversion Report (ALL PERIODS WORKING)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
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

// Summary
$summarySql = "
    SELECT 
        COUNT(DISTINCT id) as total_leads,
        COUNT(DISTINCT CASE WHEN status IN ('converted', 'active') THEN id END) as converted_leads,
        COUNT(DISTINCT CASE WHEN status IN ('lost', 'rejected') THEN id END) as lost_leads,
        COUNT(DISTINCT CASE WHEN status IN ('pending', 'new') THEN id END) as pending_leads,
        ROUND(COUNT(DISTINCT CASE WHEN status IN ('converted', 'active') THEN id END) / COUNT(DISTINCT id) * 100, 2) as conversion_rate
    FROM leads
    WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
";

$summaryResult = mysqli_query($conn, $summarySql);
$summary = mysqli_fetch_assoc($summaryResult);

if (!$summary) {
    $summary = [
        'total_leads' => 0,
        'converted_leads' => 0,
        'lost_leads' => 0,
        'pending_leads' => 0,
        'conversion_rate' => 0
    ];
}

// Period data
$periodData = [];

if ($period === 'daily') {
    $sql = "SELECT DATE(created_at) as period, COUNT(DISTINCT id) as total_leads, COUNT(DISTINCT CASE WHEN status IN ('converted','active') THEN id END) as converted, ROUND(COUNT(DISTINCT CASE WHEN status IN ('converted','active') THEN id END) / COUNT(DISTINCT id) * 100, 2) as conversion_rate FROM leads WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' GROUP BY DATE(created_at) ORDER BY period ASC LIMIT 30";
} elseif ($period === 'weekly') {
    $sql = "SELECT CONCAT(YEAR(created_at), '-W', WEEK(created_at)) as period, COUNT(DISTINCT id) as total_leads, COUNT(DISTINCT CASE WHEN status IN ('converted','active') THEN id END) as converted, ROUND(COUNT(DISTINCT CASE WHEN status IN ('converted','active') THEN id END) / COUNT(DISTINCT id) * 100, 2) as conversion_rate FROM leads WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' GROUP BY YEAR(created_at), WEEK(created_at) ORDER BY YEAR(created_at) ASC, WEEK(created_at) ASC";
} elseif ($period === 'monthly') {
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as period, COUNT(DISTINCT id) as total_leads, COUNT(DISTINCT CASE WHEN status IN ('converted','active') THEN id END) as converted, ROUND(COUNT(DISTINCT CASE WHEN status IN ('converted','active') THEN id END) / COUNT(DISTINCT id) * 100, 2) as conversion_rate FROM leads WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY period ASC";
} elseif ($period === 'quarterly') {
    // USING THE EXACT WORKING QUERY FROM THE DEBUG FILE
    $sql = "
        SELECT 
            CONCAT(q.year, '-Q', q.quarter) as period,
            q.total_leads,
            q.converted,
            ROUND(q.converted / q.total_leads * 100, 2) as conversion_rate
        FROM (
            SELECT 
                YEAR(created_at) as year,
                QUARTER(created_at) as quarter,
                COUNT(DISTINCT id) as total_leads,
                COUNT(DISTINCT CASE WHEN status IN ('converted', 'active') THEN id END) as converted
            FROM leads
            WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'
            GROUP BY YEAR(created_at), QUARTER(created_at)
        ) q
        ORDER BY q.year ASC, q.quarter ASC
    ";
} elseif ($period === 'yearly') {
    $sql = "SELECT YEAR(created_at) as period, COUNT(DISTINCT id) as total_leads, COUNT(DISTINCT CASE WHEN status IN ('converted','active') THEN id END) as converted, ROUND(COUNT(DISTINCT CASE WHEN status IN ('converted','active') THEN id END) / COUNT(DISTINCT id) * 100, 2) as conversion_rate FROM leads WHERE created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' GROUP BY YEAR(created_at) ORDER BY period ASC";
}

if ($sql) {
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $periodData[] = $row;
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Lead conversion report generated',
    'data' => [
        'period' => $period,
        'date_range' => [
            'start' => $startDate,
            'end' => $endDate
        ],
        'summary' => [
            'total_leads' => intval($summary['total_leads'] ?? 0),
            'converted_leads' => intval($summary['converted_leads'] ?? 0),
            'lost_leads' => intval($summary['lost_leads'] ?? 0),
            'pending_leads' => intval($summary['pending_leads'] ?? 0),
            'conversion_rate' => floatval($summary['conversion_rate'] ?? 0)
        ],
        'period_data' => $periodData,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>