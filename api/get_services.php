<?php
// ============================================================
// CIBIL REPAIR CRM - Get Services API
// Endpoint: /api/get_services.php
// Method: GET
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

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
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// ============================================================
// GET PARAMETERS
// ============================================================

$active = isset($_GET['active']) ? filter_var($_GET['active'], FILTER_VALIDATE_BOOLEAN) : null;
$featured = isset($_GET['featured']) ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN) : null;
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// ============================================================
// CHECK IF SERVICES TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'services'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Services table not found']);
    exit;
}

// ============================================================
# BUILD QUERY
// ============================================================

$where = [];
$params = [];
$types = '';

if ($active !== null) {
    if ($active) {
        $where[] = "status = 'active'";
    } else {
        $where[] = "status != 'active'";
    }
}

if ($featured) {
    $where[] = "is_featured = 1";
}

if (!empty($category)) {
    $where[] = "category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($search)) {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ============================================================
# GET TOTAL COUNT
// ============================================================

$countSql = "SELECT COUNT(*) as total FROM services $whereClause";
$stmt = mysqli_prepare($conn, $countSql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$countResult = mysqli_stmt_get_result($stmt);
$totalRow = mysqli_fetch_assoc($countResult);
$total = $totalRow ? intval($totalRow['total']) : 0;
mysqli_stmt_close($stmt);

// ============================================================
# GET SERVICES
// ============================================================

$sql = "SELECT * FROM services $whereClause ORDER BY is_featured DESC, is_popular DESC, id ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Decode benefits and documents
    $benefits = [];
    if (!empty($row['benefits'])) {
        $benefits = json_decode($row['benefits'], true);
        if (!is_array($benefits)) {
            $benefits = explode("\n", $row['benefits']);
        }
    }

    $documents = [];
    if (!empty($row['documents'])) {
        $documents = json_decode($row['documents'], true);
        if (!is_array($documents)) {
            $documents = explode("\n", $row['documents']);
        }
    }

    $services[] = [
        'id' => intval($row['id']),
        'name' => $row['name'] ?? '',
        'description' => $row['description'] ?? '',
        'category' => $row['category'] ?? '',
        'price' => floatval($row['price'] ?? 0),
        'duration' => $row['duration'] ?? '',
        'icon' => $row['icon'] ?? '⭐',
        'status' => $row['status'] ?? 'active',
        'is_featured' => (bool)($row['is_featured'] ?? 0),
        'is_popular' => (bool)($row['is_popular'] ?? 0),
        'benefits' => $benefits,
        'documents' => $documents,
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null
    ];
}
mysqli_stmt_close($stmt);

// ============================================================
# GET CATEGORY COUNTS
// ============================================================

$categoryCounts = [];
$catResult = mysqli_query($conn, "SELECT category, COUNT(*) as count FROM services GROUP BY category");
while ($row = mysqli_fetch_assoc($catResult)) {
    $categoryCounts[$row['category']] = intval($row['count']);
}

// ============================================================
# RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Services retrieved successfully',
    'data' => [
        'services' => $services,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'filters' => [
            'active' => $active,
            'featured' => $featured,
            'category' => $category,
            'search' => $search
        ],
        'category_counts' => $categoryCounts,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>