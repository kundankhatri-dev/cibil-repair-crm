<?php
// ============================================================
// API: Partner Leads - CORRECTED COLUMN NAMES
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];

// ========== DIRECT DATABASE CONNECTION ==========
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// Verify partner role
$result = mysqli_query($conn, "SELECT role FROM users WHERE id = $partner_id");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    if (!$row || $row['role'] !== 'partner') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        mysqli_close($conn);
        exit;
    }
}

// ========== DETERMINE TABLE ==========
$tableName = 'leads';

// ========== GET FILTERS ==========
$status = isset($_GET['status']) && $_GET['status'] !== 'all' ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$source_type = isset($_GET['source_type']) && $_GET['source_type'] !== 'all' ? mysqli_real_escape_string($conn, $_GET['source_type']) : '';

// ========== BUILD WHERE CLAUSE ==========
$where = "WHERE partner_id = $partner_id";
if (!empty($status)) {
    $where .= " AND status = '$status'";
}
if (!empty($search)) {
    $where .= " AND (name LIKE '%$search%' OR phone LIKE '%$search%' OR email LIKE '%$search%')";
}
if (!empty($source_type)) {
    $where .= " AND source_type = '$source_type'";
}

// ========== GET LEADS ==========
$query = "SELECT 
    id,
    name,
    phone,
    email,
    service_type,
    source,
    status,
    created_at,
    amount,
    score,
    priority,
    source_type,
    source_id,
    source_name,
    source_commission_rate,
    source_commission_amount
FROM $tableName 
$where 
ORDER BY id DESC 
LIMIT 50";

error_log('get_leads.php query: ' . $query);

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        'success' => false, 
        'error' => 'Query failed: ' . mysqli_error($conn)
    ]);
    mysqli_close($conn);
    exit;
}

$leads = [];
while ($row = mysqli_fetch_assoc($result)) {
    $leads[] = [
        'id' => (int)$row['id'],
        'customer_name' => $row['name'] ?? '—',         // Map name → customer_name
        'customer_phone' => $row['phone'] ?? '—',       // Map phone → customer_phone
        'customer_email' => $row['email'] ?? '—',       // Map email → customer_email
        'service_type' => $row['service_type'] ?? '—',
        'source' => $row['source'] ?? '—',
        'status' => $row['status'] ?? 'new',
        'created_at' => $row['created_at'],
        'amount' => (float)($row['amount'] ?? 0),
        'score' => (int)($row['score'] ?? 0),
        'priority' => $row['priority'] ?? 'low',
        'source_type' => $row['source_type'] ?? 'direct',
        'source_id' => $row['source_id'] ? (int)$row['source_id'] : null,
        'source_name' => $row['source_name'] ?? '',
        'source_commission_rate' => (float)($row['source_commission_rate'] ?? 0),
        'source_commission_amount' => (float)($row['source_commission_amount'] ?? 0)
    ];
}

// ========== GET TOTAL COUNT ==========
$total = 0;
$countQuery = "SELECT COUNT(*) as cnt FROM $tableName $where";
$countResult = mysqli_query($conn, $countQuery);
if ($countResult) {
    $row = mysqli_fetch_assoc($countResult);
    $total = (int)($row['cnt'] ?? 0);
}

echo json_encode([
    'success' => true,
    'leads' => $leads,
    'total' => $total,
    'total_all' => $total,
    'debug' => [
        'partner_id' => $partner_id,
        'table' => $tableName,
        'row_count' => count($leads),
        'status_filter' => $status
    ]
]);

mysqli_close($conn);
?>