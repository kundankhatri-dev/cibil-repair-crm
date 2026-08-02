<?php
// ============================================================
// CIBIL REPAIR CRM - Get Expense Report API (with GST)
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
// GST CONFIGURATION
// ============================================================

define('GST_RATE', 18);
define('GST_CGST', 9);
define('GST_SGST', 9);

// ============================================================
// GET PARAMETERS
// ============================================================

$report_type = isset($_GET['report_type']) ? trim($_GET['report_type']) : 'summary';
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// ============================================================
// BUILD DATE FILTERS
// ============================================================

$dateWhere = '';
$dateParams = [];
$dateTypes = '';

if (!empty($from_date) && !empty($to_date)) {
    $dateWhere = "AND DATE(date) BETWEEN '$from_date' AND '$to_date'";
} elseif (!empty($from_date)) {
    $dateWhere = "AND DATE(date) >= '$from_date'";
} elseif (!empty($to_date)) {
    $dateWhere = "AND DATE(date) <= '$to_date'";
}

// ============================================================
// GENERATE REPORT
// ============================================================

try {
    $reportData = [];

    switch ($report_type) {
        case 'summary':
            $reportData = getExpenseSummary($conn, $dateWhere, $category, $status);
            break;
        case 'monthly':
            $reportData = getExpenseMonthly($conn, $year, $category, $status);
            break;
        case 'yearly':
            $reportData = getExpenseYearly($conn, $category, $status);
            break;
        case 'category':
            $reportData = getExpenseByCategory($conn, $dateWhere, $category, $status);
            break;
        case 'detailed':
        default:
            $reportData = getExpenseDetailed($conn, $dateWhere, $category, $status);
            break;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Expense report generated successfully',
        'data' => [
            'report_type' => $report_type,
            'filters' => [
                'from_date' => $from_date,
                'to_date' => $to_date,
                'year' => $year,
                'month' => $month,
                'category' => $category,
                'status' => $status
            ],
            'report' => $reportData,
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error generating report: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
exit;

// ============================================================
// REPORT GENERATOR FUNCTIONS
// ============================================================

function getExpenseSummary($conn, $dateWhere, $category, $status) {
    $where = "1=1 $dateWhere";
    if (!empty($category)) $where .= " AND category = '$category'";
    if (!empty($status)) $where .= " AND status = '$status'";

    $result = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT 
            COUNT(*) as total_count,
            IFNULL(SUM(amount), 0) as total_amount,
            IFNULL(SUM(total_with_gst), 0) as total_with_gst,
            IFNULL(SUM(gst_amount), 0) as total_gst,
            IFNULL(AVG(amount), 0) as avg_amount
        FROM expenses WHERE $where"
    ));

    $categories = [];
    $r = mysqli_query($conn, 
        "SELECT category, COUNT(*) as count, IFNULL(SUM(amount), 0) as amount 
        FROM expenses WHERE $where GROUP BY category ORDER BY amount DESC"
    );
    while ($row = mysqli_fetch_assoc($r)) {
        $categories[] = $row;
    }

    return [
        'summary' => [
            'total_expenses' => (int)($result['total_count'] ?? 0),
            'total_amount' => (float)($result['total_amount'] ?? 0),
            'total_with_gst' => (float)($result['total_with_gst'] ?? 0),
            'total_gst' => (float)($result['total_gst'] ?? 0),
            'average_expense' => (float)($result['avg_amount'] ?? 0)
        ],
        'category_breakdown' => $categories
    ];
}

function getExpenseDetailed($conn, $dateWhere, $category, $status) {
    $where = "1=1 $dateWhere";
    if (!empty($category)) $where .= " AND category = '$category'";
    if (!empty($status)) $where .= " AND status = '$status'";

    $expenses = [];
    $r = mysqli_query($conn, 
        "SELECT id, category, description, amount, total_with_gst, gst_amount, 
                cgst_amount, sgst_amount, is_gst_applicable, date, expense_type, 
                payment_method, vendor_name, status, notes, created_at 
        FROM expenses WHERE $where ORDER BY date DESC"
    );
    while ($row = mysqli_fetch_assoc($r)) {
        $expenses[] = $row;
    }

    return [
        'total_records' => count($expenses),
        'expenses' => $expenses
    ];
}

function getExpenseMonthly($conn, $year, $category, $status) {
    $where = "YEAR(date) = $year";
    if (!empty($category)) $where .= " AND category = '$category'";
    if (!empty($status)) $where .= " AND status = '$status'";

    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $monthlyStats = [];

    for ($i = 1; $i <= 12; $i++) {
        $r = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT COUNT(*) as count, IFNULL(SUM(amount), 0) as total 
            FROM expenses WHERE $where AND MONTH(date) = $i"
        ));
        $monthlyStats[$monthNames[$i-1]] = [
            'count' => (int)($r['count'] ?? 0),
            'total' => (float)($r['total'] ?? 0)
        ];
    }

    return [
        'year' => $year,
        'months' => $monthlyStats,
        'totals' => [
            'total_expenses' => array_sum(array_column($monthlyStats, 'count')),
            'total_amount' => array_sum(array_column($monthlyStats, 'total'))
        ]
    ];
}

function getExpenseYearly($conn, $category, $status) {
    $where = "1=1";
    if (!empty($category)) $where .= " AND category = '$category'";
    if (!empty($status)) $where .= " AND status = '$status'";

    $years = [];
    for ($i = 4; $i >= 0; $i--) {
        $y = date('Y') - $i;
        $r = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT COUNT(*) as count, IFNULL(SUM(amount), 0) as total 
            FROM expenses WHERE $where AND YEAR(date) = $y"
        ));
        $years[$y] = [
            'count' => (int)($r['count'] ?? 0),
            'total' => (float)($r['total'] ?? 0)
        ];
    }

    return ['years' => $years];
}

function getExpenseByCategory($conn, $dateWhere, $category, $status) {
    $where = "1=1 $dateWhere";
    if (!empty($category)) $where .= " AND category = '$category'";
    if (!empty($status)) $where .= " AND status = '$status'";

    $categories = [];
    $r = mysqli_query($conn, 
        "SELECT category, COUNT(*) as count, IFNULL(SUM(amount), 0) as total 
        FROM expenses WHERE $where GROUP BY category ORDER BY total DESC"
    );
    while ($row = mysqli_fetch_assoc($r)) {
        $categories[] = $row;
    }

    return [
        'categories' => $categories,
        'totals' => [
            'total_amount' => array_sum(array_column($categories, 'total')),
            'total_expenses' => array_sum(array_column($categories, 'count'))
        ]
    ];
}
?>