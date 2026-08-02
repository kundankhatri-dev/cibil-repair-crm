<?php
// ============================================================
// CIBIL REPAIR CRM - Get Chart Data API (FIXED)
// ============================================================

// Disable error display
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
    exit;
}

$userRole = $_SESSION['user_role'] ?? '';

// Database connection
$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GET PARAMETERS
// ============================================================

$mode = isset($_GET['mode']) ? trim($_GET['mode']) : 'monthly';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : date('Y-m-01');
$toDate = isset($_GET['to_date']) ? trim($_GET['to_date']) : date('Y-m-d');

// Validate mode
$allowedModes = ['monthly', 'weekly', 'yearly', 'daily', 'custom'];
if (!in_array($mode, $allowedModes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid mode']);
    mysqli_close($conn);
    exit;
}

// ============================================================
// GET CHART DATA
// ============================================================

try {
    $chartData = [];
    
    switch ($mode) {
        case 'monthly':
            $chartData = getMonthlyChartData($conn, $year);
            break;
        case 'weekly':
            $chartData = getWeeklyChartData($conn, $year, $month);
            break;
        case 'yearly':
            $chartData = getYearlyChartData($conn);
            break;
        case 'daily':
            $chartData = getDailyChartData($conn, $year, $month);
            break;
        case 'custom':
            $chartData = getCustomChartData($conn, $fromDate, $toDate);
            break;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Chart data retrieved successfully',
        'data' => [
            'mode' => $mode,
            'labels' => $chartData['labels'],
            'values' => $chartData['values'],
            'datasets' => isset($chartData['datasets']) ? $chartData['datasets'] : null,
            'summary' => isset($chartData['summary']) ? $chartData['summary'] : null
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching chart data: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
exit;

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function getMonthlyChartData($conn, $year) {
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                   'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    $monthlyData = array_fill(1, 12, 0);
    $expenseData = array_fill(1, 12, 0);
    
    // Get sales data (using sale_date)
    $query = "SELECT MONTH(sale_date) as month, IFNULL(SUM(amount), 0) as total 
              FROM sales WHERE status = 'completed' AND YEAR(sale_date) = $year 
              GROUP BY MONTH(sale_date)";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $monthlyData[(int)$row['month']] = (float)$row['total'];
        }
        mysqli_free_result($result);
    }
    
    // Get expense data (using date)
    $query = "SELECT MONTH(date) as month, IFNULL(SUM(amount), 0) as total 
              FROM expenses WHERE YEAR(date) = $year 
              GROUP BY MONTH(date)";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $expenseData[(int)$row['month']] = (float)$row['total'];
        }
        mysqli_free_result($result);
    }
    
    return [
        'labels' => $monthNames,
        'values' => array_values($monthlyData),
        'datasets' => [
            [
                'label' => 'Revenue',
                'data' => array_values($monthlyData),
                'backgroundColor' => 'rgba(13, 158, 120, 0.2)',
                'borderColor' => '#0d9e78',
                'fill' => true
            ],
            [
                'label' => 'Expenses',
                'data' => array_values($expenseData),
                'backgroundColor' => 'rgba(220, 38, 38, 0.2)',
                'borderColor' => '#dc2626',
                'fill' => true
            ]
        ],
        'summary' => [
            'total_revenue' => array_sum($monthlyData),
            'total_expenses' => array_sum($expenseData),
            'net_profit' => array_sum($monthlyData) - array_sum($expenseData)
        ]
    ];
}

function getWeeklyChartData($conn, $year, $month) {
    $weekLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    $weekData = array_fill(0, 4, 0);
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    for ($w = 0; $w < 4; $w++) {
        $startDay = $w * 7 + 1;
        $endDay = min(($w + 1) * 7, $daysInMonth);
        
        $query = "SELECT IFNULL(SUM(amount), 0) as total FROM sales 
                  WHERE status = 'completed' AND YEAR(sale_date) = $year 
                  AND MONTH(sale_date) = $month AND DAY(sale_date) BETWEEN $startDay AND $endDay";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $weekData[$w] = $row ? (float)$row['total'] : 0;
            mysqli_free_result($result);
        }
    }
    
    return [
        'labels' => $weekLabels,
        'values' => $weekData,
        'summary' => ['total' => array_sum($weekData)]
    ];
}

function getYearlyChartData($conn) {
    $yearLabels = [];
    $yearData = [];
    
    for ($i = 4; $i >= 0; $i--) {
        $y = date('Y') - $i;
        $yearLabels[] = $y;
        
        $query = "SELECT IFNULL(SUM(amount), 0) as total FROM sales 
                  WHERE status = 'completed' AND YEAR(sale_date) = $y";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $yearData[] = $row ? (float)$row['total'] : 0;
            mysqli_free_result($result);
        }
    }
    
    return [
        'labels' => $yearLabels,
        'values' => $yearData,
        'summary' => ['total' => array_sum($yearData)]
    ];
}

function getDailyChartData($conn, $year, $month) {
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $dailyLabels = [];
    $dailyData = [];
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $dailyLabels[] = $day;
        $query = "SELECT IFNULL(SUM(amount), 0) as total FROM sales 
                  WHERE status = 'completed' AND YEAR(sale_date) = $year 
                  AND MONTH(sale_date) = $month AND DAY(sale_date) = $day";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $dailyData[] = $row ? (float)$row['total'] : 0;
            mysqli_free_result($result);
        }
    }
    
    return [
        'labels' => $dailyLabels,
        'values' => $dailyData,
        'summary' => ['total' => array_sum($dailyData)]
    ];
}

function getCustomChartData($conn, $fromDate, $toDate) {
    $query = "SELECT DATE(sale_date) as date_key, IFNULL(SUM(amount), 0) as total 
              FROM sales WHERE status = 'completed' 
              AND DATE(sale_date) BETWEEN '$fromDate' AND '$toDate' 
              GROUP BY DATE(sale_date) ORDER BY date_key ASC";
    $result = mysqli_query($conn, $query);
    
    $labels = [];
    $values = [];
    $total = 0;
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $labels[] = date('M d', strtotime($row['date_key']));
            $values[] = (float)$row['total'];
            $total += (float)$row['total'];
        }
        mysqli_free_result($result);
    }
    
    return [
        'labels' => $labels,
        'values' => $values,
        'summary' => ['total' => $total, 'days' => count($values)]
    ];
}
?>