<?php
// ============================================================
// CIBIL REPAIR CRM - Get Partner Applications API (FULLY FIXED)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

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

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ============================================================
// CREATE TABLE IF NOT EXISTS
// ============================================================

$createTable = "CREATE TABLE IF NOT EXISTS partner_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    partner_type VARCHAR(50) DEFAULT 'Business',
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    notes TEXT,
    rejection_reason TEXT,
    ref_number VARCHAR(50),
    approved_by INT,
    approved_at DATETIME,
    application_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

mysqli_query($conn, $createTable);

// ============================================================
// GET SINGLE APPLICATION
// ============================================================

if ($id > 0) {
    $sql = "SELECT * FROM partner_applications WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $application = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($application) {
        echo json_encode(['success' => true, 'data' => $application]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Application not found']);
    }
    mysqli_close($conn);
    exit;
}

// ============================================================
// BUILD WHERE CLAUSE - FIXED
// ============================================================

$whereClause = '';
$params = [];
$types = '';

// IMPORTANT FIX: Build the WHERE clause correctly
if (!empty($status) && $status !== 'all' && $status !== '') {
    $whereClause = "WHERE status = ?";
    $params[] = $status;
    $types = 's';
}

// Debug: log the query parameters
error_log("Status filter: '$status'");
error_log("Where clause: '$whereClause'");

// ============================================================
// GET TOTAL COUNT
// ============================================================

$countSql = "SELECT COUNT(*) as total FROM partner_applications " . $whereClause;
$stmt = mysqli_prepare($conn, $countSql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$countResult = mysqli_stmt_get_result($stmt);
$totalRow = mysqli_fetch_assoc($countResult);
$total = $totalRow['total'] ?? 0;
mysqli_stmt_close($stmt);

// ============================================================
// GET APPLICATIONS
// ============================================================

$orderBy = "CASE WHEN status = 'pending' THEN 0 ELSE 1 END, created_at DESC";

$sql = "SELECT * FROM partner_applications " . $whereClause . " ORDER BY " . $orderBy . " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$applications = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format dates
    if (isset($row['application_date'])) {
        $row['submitted_date'] = $row['application_date'];
    } elseif (isset($row['created_at'])) {
        $row['submitted_date'] = $row['created_at'];
    }
    
    if (isset($row['submitted_date'])) {
        $row['formatted_date'] = date('d M Y, h:i A', strtotime($row['submitted_date']));
    }
    
    $applications[] = $row;
}
mysqli_stmt_close($stmt);

// ============================================================
// GET STATUS COUNTS
// ============================================================

$statusCounts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach (['pending', 'approved', 'rejected'] as $s) {
    $sql = "SELECT COUNT(*) as count FROM partner_applications WHERE status = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $s);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $statusCounts[$s] = $row['count'] ?? 0;
    mysqli_stmt_close($stmt);
}
$statusCounts['total'] = array_sum($statusCounts);

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'data' => $applications,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset,
    'filters' => [
        'status' => $status
    ],
    'status_counts' => $statusCounts,
    'timestamp' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>