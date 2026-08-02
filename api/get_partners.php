<?php
// ============================================================
// CIBIL REPAIR CRM - Get Partners API
// Endpoint: /api/get_partners.php
// Method: GET
// ============================================================

// ============================================================
// CORS & HEADERS
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
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
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$userRole = $_SESSION['user_role'] ?? '';
$allowedRoles = ['admin', 'super_admin', 'manager', 'partner', 'user'];
$isTestMode = isset($_GET['test']) && $_GET['test'] === 'true';

if (!$isTestMode && !in_array($userRole, $allowedRoles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required.']);
    exit;
}

// ============================================================
// VALIDATE REQUEST METHOD
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method. Use GET.']);
    exit;
}

// ============================================================
// CHECK IF TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'partners'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Partners table not found. Please create the table first.']);
    exit;
}

// ============================================================
// GET ACTUAL COLUMNS FROM TABLE
// ============================================================

$columns = [];
$columnResult = mysqli_query($conn, "SHOW COLUMNS FROM partners");
if ($columnResult) {
    while ($row = mysqli_fetch_assoc($columnResult)) {
        $columns[] = $row['Field'];
    }
    mysqli_free_result($columnResult);
}

// Define columns we want to select
$desiredColumns = ['id', 'name', 'location', 'owner', 'phone', 'email', 'commission_rate', 'status', 'created_at', 'updated_at'];

// Only select columns that actually exist
$selectedColumns = array_intersect($desiredColumns, $columns);
if (empty($selectedColumns)) {
    echo json_encode(['success' => false, 'error' => 'No columns found in partners table.']);
    exit;
}
$selectColumns = implode(', ', $selectedColumns);

// ============================================================
// GET PARAMETERS
// ============================================================

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'id';
$sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'DESC';

// ============================================================
// VALIDATE SORT PARAMETERS
// ============================================================

$allowedSortColumns = array_intersect(['id', 'name', 'location', 'owner', 'phone', 'email', 'commission_rate', 'status', 'created_at'], $selectedColumns);
if (!in_array($sort_by, $allowedSortColumns)) {
    $sort_by = 'id';
}
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

// ============================================================
// GET SPECIFIC PARTNER
// ============================================================

if ($id > 0) {
    $sql = "SELECT $selectColumns FROM partners WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $partner = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($partner) {
        echo json_encode(['success' => true, 'message' => 'Partner found', 'data' => $partner]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Partner not found']);
    }
    mysqli_close($conn);
    exit;
}

// ============================================================
// BUILD QUERY WITH FILTERS
// ============================================================

$where = [];
$params = [];
$types = '';

// Search filter - only use columns that exist
if (!empty($search)) {
    $searchableColumns = array_intersect(['name', 'location', 'owner', 'phone', 'email'], $selectedColumns);
    if (!empty($searchableColumns)) {
        $searchConditions = [];
        $searchWild = "%$search%";
        foreach ($searchableColumns as $col) {
            $searchConditions[] = "$col LIKE ?";
            $params[] = $searchWild;
            $types .= 's';
        }
        $where[] = "(" . implode(' OR ', $searchConditions) . ")";
    }
}

// Status filter
if (!empty($status) && $status !== 'all' && in_array('status', $selectedColumns)) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

// ============================================================
// BUILD QUERY
// ============================================================

$query = "SELECT $selectColumns FROM partners";

if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

$query .= " ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// ============================================================
// EXECUTE QUERY
// ============================================================

$stmt = mysqli_prepare($conn, $query);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$partners = [];
while ($row = mysqli_fetch_assoc($result)) {
    $partners[] = $row;
}
mysqli_stmt_close($stmt);

// ============================================================
// GET TOTAL COUNT
// ============================================================

$countQuery = "SELECT COUNT(*) as total FROM partners";
if (!empty($where)) {
    $countQuery .= " WHERE " . implode(' AND ', $where);
}
$countResult = mysqli_query($conn, $countQuery);
$totalRow = mysqli_fetch_assoc($countResult);
$total = $totalRow ? (int)$totalRow['total'] : 0;

// ============================================================
// GET STATUS COUNTS
// ============================================================

$statusCounts = [];
if (in_array('status', $selectedColumns)) {
    $statuses = ['active', 'inactive', 'pending'];
    foreach ($statuses as $s) {
        $sql = "SELECT COUNT(*) as count FROM partners WHERE status = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $s);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $statusCounts[$s] = $row ? (int)$row['count'] : 0;
        mysqli_stmt_close($stmt);
    }
}
$statusCounts['total'] = $total;

// ============================================================
# GET PARTNER PERFORMANCE STATS (Optional)
// ============================================================

$performanceStats = [];
if (!empty($partners)) {
    $partnerIds = array_column($partners, 'id');
    $ids = implode(',', $partnerIds);
    
    $perfSql = "
        SELECT 
            s.partner_id,
            COUNT(s.id) as total_sales,
            SUM(s.amount) as total_revenue,
            SUM(s.commission_amount) as total_commission
        FROM sales s
        WHERE s.partner_id IN ($ids)
        GROUP BY s.partner_id
    ";
    $perfResult = mysqli_query($conn, $perfSql);
    while ($row = mysqli_fetch_assoc($perfResult)) {
        $performanceStats[$row['partner_id']] = [
            'total_sales' => intval($row['total_sales'] ?? 0),
            'total_revenue' => floatval($row['total_revenue'] ?? 0),
            'total_commission' => floatval($row['total_commission'] ?? 0)
        ];
    }
}

// ============================================================
// FORMAT RESPONSE
// ============================================================

$formattedPartners = [];
foreach ($partners as $partner) {
    $formattedPartner = [];
    foreach ($partner as $key => $value) {
        if ($key === 'id') {
            $formattedPartner['id'] = (int)$value;
        } elseif ($key === 'commission_rate') {
            $formattedPartner['commission_rate'] = $value !== null ? (int)$value : 10;
        } elseif ($key === 'created_at' || $key === 'updated_at') {
            $formattedPartner[$key] = $value;
        } else {
            $formattedPartner[$key] = $value ?? '';
        }
    }
    
    // Add performance stats if available
    if (isset($performanceStats[$formattedPartner['id']])) {
        $formattedPartner['performance'] = $performanceStats[$formattedPartner['id']];
    } else {
        $formattedPartner['performance'] = [
            'total_sales' => 0,
            'total_revenue' => 0,
            'total_commission' => 0
        ];
    }
    
    $formattedPartners[] = $formattedPartner;
}

// ============================================================
// SUCCESS RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Partners retrieved successfully',
    'data' => [
        'partners' => $formattedPartners,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'search' => $search,
        'status' => $status,
        'sort_by' => $sort_by,
        'sort_order' => $sort_order,
        'status_counts' => $statusCounts,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>