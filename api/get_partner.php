<?php
// ============================================================
// CIBIL REPAIR CRM - Get Single Partner API (SIMPLIFIED)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database connection
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

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Get ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Partner ID is required']);
    exit;
}

// Get partner
$sql = "SELECT * FROM partners WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$partner = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$partner) {
    echo json_encode(['success' => false, 'error' => 'Partner not found']);
    exit;
}

// Get stats
$stats = [
    'total_revenue' => 0,
    'total_sales' => 0,
    'total_leads' => 0,
    'converted_leads' => 0,
    'conversion_rate' => 0,
    'total_commission' => 0,
    'last_activity' => null
];

// Total revenue
$revenueResult = mysqli_query($conn, "SELECT IFNULL(SUM(amount), 0) as total FROM sales WHERE partner_id = $id AND status = 'Completed'");
if ($revenueResult) {
    $row = mysqli_fetch_assoc($revenueResult);
    $stats['total_revenue'] = (float)($row['total'] ?? 0);
}

// Total sales
$salesCountResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM sales WHERE partner_id = $id");
if ($salesCountResult) {
    $row = mysqli_fetch_assoc($salesCountResult);
    $stats['total_sales'] = (int)($row['total'] ?? 0);
}

// Total leads
$leadsCountResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM leads WHERE partner_id = $id");
if ($leadsCountResult) {
    $row = mysqli_fetch_assoc($leadsCountResult);
    $stats['total_leads'] = (int)($row['total'] ?? 0);
}

// Converted leads
$convertedLeadsResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM leads WHERE partner_id = $id AND status = 'converted'");
if ($convertedLeadsResult) {
    $row = mysqli_fetch_assoc($convertedLeadsResult);
    $stats['converted_leads'] = (int)($row['total'] ?? 0);
}

// Conversion rate
$stats['conversion_rate'] = $stats['total_leads'] > 0 ? round(($stats['converted_leads'] / $stats['total_leads']) * 100, 1) : 0;

// Recent sales
$sales = [];
$salesSql = "SELECT id, customer_name, service, amount, sale_date as date, status FROM sales WHERE partner_id = $id ORDER BY sale_date DESC LIMIT 5";
$salesResult = mysqli_query($conn, $salesSql);
if ($salesResult) {
    while ($row = mysqli_fetch_assoc($salesResult)) {
        $sales[] = $row;
    }
}

// Recent leads
$leads = [];
$leadsSql = "SELECT id, name, phone, email, service, status, created_at FROM leads WHERE partner_id = $id ORDER BY created_at DESC LIMIT 5";
$leadsResult = mysqli_query($conn, $leadsSql);
if ($leadsResult) {
    while ($row = mysqli_fetch_assoc($leadsResult)) {
        $leads[] = $row;
    }
}

// Format response
$response = [
    'id' => (int)$partner['id'],
    'name' => $partner['name'] ?? '',
    'location' => $partner['location'] ?? '',
    'owner' => $partner['owner'] ?? '',
    'phone' => $partner['phone'] ?? '',
    'email' => $partner['email'] ?? '',
    'company_name' => $partner['company_name'] ?? $partner['name'] ?? '',
    'commission_rate' => (int)($partner['commission_rate'] ?? 10),
    'status' => $partner['status'] ?? 'active',
    'total_leads' => (int)($partner['total_leads'] ?? 0),
    'total_converted' => (int)($partner['total_converted'] ?? 0),
    'tier_level' => $partner['tier_level'] ?? 'basic',
    'kyc_status' => $partner['kyc_status'] ?? 'pending',
    'created_at' => $partner['created_at'] ?? null,
    'stats' => $stats,
    'recent_sales' => $sales,
    'recent_leads' => $leads
];

echo json_encode([
    'success' => true,
    'message' => 'Partner retrieved successfully',
    'data' => $response,
    'timestamp' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>