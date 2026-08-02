<?php
// ============================================================
// CIBIL REPAIR CRM - Get Expenses API (COMPLETE FIX)
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

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// ============================================================
// GET SINGLE EXPENSE
// ============================================================

if ($id > 0) {
    $result = mysqli_query($conn, "SELECT * FROM expenses WHERE id = $id");
    $expense = mysqli_fetch_assoc($result);
    
    if (!$expense) {
        echo json_encode(['success' => false, 'error' => 'Expense not found']);
        mysqli_close($conn);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $expense
    ]);
    mysqli_close($conn);
    exit;
}

// ============================================================
// BUILD WHERE CLAUSE
// ============================================================

$where = [];

if (!empty($search)) {
    $where[] = "(category LIKE '%$search%' OR description LIKE '%$search%' OR vendor_name LIKE '%$search%')";
}

if (!empty($category)) {
    $where[] = "category = '$category'";
}

if (!empty($status)) {
    $where[] = "status = '$status'";
}

if (!empty($from_date)) {
    $where[] = "date >= '$from_date'";
}

if (!empty($to_date)) {
    $where[] = "date <= '$to_date'";
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// ============================================================
// GET EXPENSES
// ============================================================

$query = "SELECT id, category, description, amount, date, expense_type, payment_method, 
          vendor_name, status, created_at 
          FROM expenses $whereClause 
          ORDER BY created_at DESC 
          LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Query failed: ' . mysqli_error($conn)]);
    mysqli_close($conn);
    exit;
}

$expenses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $expenses[] = $row;
}

// ============================================================
// GET TOTAL COUNT
// ============================================================

$countQuery = "SELECT COUNT(*) as total FROM expenses $whereClause";
$countResult = mysqli_query($conn, $countQuery);
$total = $countResult ? (int)mysqli_fetch_assoc($countResult)['total'] : 0;

// ============================================================
// GET CATEGORY COUNTS
// ============================================================

$categoryCounts = [];
$catResult = mysqli_query($conn, "SELECT category, COUNT(*) as count FROM expenses GROUP BY category");
if ($catResult) {
    while ($row = mysqli_fetch_assoc($catResult)) {
        $categoryCounts[$row['category']] = (int)$row['count'];
    }
}

// ============================================================
// GET STATUS COUNTS
// ============================================================

$statusCounts = ['total' => $total];
$statuses = ['pending', 'approved', 'rejected', 'paid'];
foreach ($statuses as $s) {
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM expenses WHERE status = '$s'"));
    $statusCounts[$s] = $r ? (int)$r['count'] : 0;
}

// ============================================================
// GET SUMMARY STATS
// ============================================================

$stats = [];

$r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(amount), 0) as total FROM expenses $whereClause"));
$stats['total_amount'] = $r ? (float)$r['total'] : 0;

$r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(AVG(amount), 0) as avg FROM expenses $whereClause"));
$stats['average_amount'] = $r ? (float)$r['avg'] : 0;

$stats['total_expenses'] = $total;

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'data' => [
        'expenses' => $expenses,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'filters' => [
            'search' => $search,
            'category' => $category,
            'status' => $status,
            'from_date' => $from_date,
            'to_date' => $to_date
        ],
        'category_counts' => $categoryCounts,
        'status_counts' => $statusCounts,
        'stats' => $stats
    ]
]);

mysqli_close($conn);
exit;
?>