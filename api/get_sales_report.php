<?php
// ============================================================
// CIBIL REPAIR CRM - Get Sales Report (SIMPLE)
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

$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$report_type = isset($_GET['report_type']) ? trim($_GET['report_type']) : 'summary';

// Build date filter
$dateFilter = "";
if (!empty($from_date) && !empty($to_date)) {
    $dateFilter = "WHERE sale_date BETWEEN '$from_date' AND '$to_date'";
} elseif (!empty($from_date)) {
    $dateFilter = "WHERE sale_date >= '$from_date'";
} elseif (!empty($to_date)) {
    $dateFilter = "WHERE sale_date <= '$to_date'";
}

// Summary report
if ($report_type === 'summary') {
    // Total summary
    $sql = "SELECT COUNT(*) as total_sales, SUM(amount) as total_revenue, AVG(amount) as avg_amount, MAX(amount) as max_amount, MIN(amount) as min_amount FROM sales $dateFilter";
    $result = mysqli_query($conn, $sql);
    $summary = mysqli_fetch_assoc($result);

    // Status breakdown
    $statusSql = "SELECT status, COUNT(*) as count, SUM(amount) as total FROM sales $dateFilter GROUP BY status";
    $statusResult = mysqli_query($conn, $statusSql);
    $statusBreakdown = [];
    while ($row = mysqli_fetch_assoc($statusResult)) {
        $statusBreakdown[] = $row;
    }

    // Service breakdown
    $serviceSql = "SELECT service, COUNT(*) as count, SUM(amount) as total FROM sales $dateFilter GROUP BY service ORDER BY total DESC";
    $serviceResult = mysqli_query($conn, $serviceSql);
    $serviceBreakdown = [];
    while ($row = mysqli_fetch_assoc($serviceResult)) {
        $serviceBreakdown[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'report_type' => 'summary',
            'summary' => [
                'total_sales' => intval($summary['total_sales'] ?? 0),
                'total_revenue' => floatval($summary['total_revenue'] ?? 0),
                'avg_amount' => floatval($summary['avg_amount'] ?? 0),
                'max_amount' => floatval($summary['max_amount'] ?? 0),
                'min_amount' => floatval($summary['min_amount'] ?? 0)
            ],
            'status_breakdown' => $statusBreakdown,
            'service_breakdown' => $serviceBreakdown,
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ]);
    mysqli_close($conn);
    exit;
}

// Detailed report
if ($report_type === 'detailed') {
    $sql = "SELECT id, customer_name, service, amount, status, sale_date FROM sales $dateFilter ORDER BY sale_date DESC";
    $result = mysqli_query($conn, $sql);
    $sales = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sales[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'report_type' => 'detailed',
            'total_records' => count($sales),
            'sales' => $sales,
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ]);
    mysqli_close($conn);
    exit;
}

// Default
echo json_encode([
    'success' => true,
    'message' => 'Report generated',
    'data' => [
        'report_type' => $report_type,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>