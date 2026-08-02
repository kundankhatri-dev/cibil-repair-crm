<?php
// ============================================================
// CIBIL REPAIR CRM - Get Single Service API
// Endpoint: /api/get_service.php
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

$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$service_name = isset($_GET['name']) ? trim($_GET['name']) : '';

if ($service_id <= 0 && empty($service_name)) {
    echo json_encode(['success' => false, 'error' => 'Service ID or Name is required']);
    exit;
}

// ============================================================
// CHECK IF SERVICES TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'services'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Services table not found']);
    exit;
}

// ============================================================
// GET SERVICE
// ============================================================

if ($service_id > 0) {
    $sql = "SELECT * FROM services WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $service_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $service = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    $sql = "SELECT * FROM services WHERE name = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $service_name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $service = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$service) {
    echo json_encode(['success' => false, 'error' => 'Service not found']);
    exit;
}

// ============================================================
// GET RELATED DATA
// ============================================================

// Total bookings
$bSql = "SELECT COUNT(*) as total FROM sales WHERE service = ?";
$bStmt = mysqli_prepare($conn, $bSql);
mysqli_stmt_bind_param($bStmt, 's', $service['name']);
mysqli_stmt_execute($bStmt);
$bResult = mysqli_stmt_get_result($bStmt);
$bRow = mysqli_fetch_assoc($bResult);
$bookingCount = $bRow ? intval($bRow['total']) : 0;
mysqli_stmt_close($bStmt);

// Total revenue
$rSql = "SELECT SUM(amount) as total FROM sales WHERE service = ?";
$rStmt = mysqli_prepare($conn, $rSql);
mysqli_stmt_bind_param($rStmt, 's', $service['name']);
mysqli_stmt_execute($rStmt);
$rResult = mysqli_stmt_get_result($rStmt);
$rRow = mysqli_fetch_assoc($rResult);
$totalRevenue = $rRow ? floatval($rRow['total'] ?? 0) : 0;
mysqli_stmt_close($rStmt);

// Recent bookings
$recentBookings = [];
$rbSql = "SELECT id, customer_name, amount, sale_date, status FROM sales WHERE service = ? ORDER BY sale_date DESC LIMIT 5";
$rbStmt = mysqli_prepare($conn, $rbSql);
mysqli_stmt_bind_param($rbStmt, 's', $service['name']);
mysqli_stmt_execute($rbStmt);
$rbResult = mysqli_stmt_get_result($rbStmt);
while ($row = mysqli_fetch_assoc($rbResult)) {
    $recentBookings[] = $row;
}
mysqli_stmt_close($rbStmt);

// ============================================================
// DECODE JSON FIELDS
// ============================================================

$benefits = [];
if (!empty($service['benefits'])) {
    $benefits = json_decode($service['benefits'], true);
    if (!is_array($benefits)) {
        $benefits = explode("\n", $service['benefits']);
    }
}

$documents = [];
if (!empty($service['documents'])) {
    $documents = json_decode($service['documents'], true);
    if (!is_array($documents)) {
        $documents = explode("\n", $service['documents']);
    }
}

// ============================================================
// FORMAT RESPONSE
// ============================================================

$formattedService = [
    'id' => intval($service['id']),
    'name' => $service['name'] ?? '',
    'description' => $service['description'] ?? '',
    'category' => $service['category'] ?? '',
    'price' => floatval($service['price'] ?? 0),
    'duration' => $service['duration'] ?? '',
    'icon' => $service['icon'] ?? '⭐',
    'status' => $service['status'] ?? 'active',
    'is_featured' => (bool)($service['is_featured'] ?? 0),
    'is_popular' => (bool)($service['is_popular'] ?? 0),
    'benefits' => $benefits,
    'documents' => $documents,
    'stats' => [
        'total_bookings' => $bookingCount,
        'total_revenue' => $totalRevenue
    ],
    'recent_bookings' => $recentBookings,
    'created_at' => $service['created_at'] ?? null,
    'updated_at' => $service['updated_at'] ?? null
];

// ============================================================
// SUCCESS RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Service retrieved successfully',
    'data' => $formattedService,
    'generated_at' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>