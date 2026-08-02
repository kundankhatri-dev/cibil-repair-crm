<?php
// ============================================================
// CIBIL REPAIR CRM - Get Stats API
// Endpoint: /api/get_stats.php
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
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
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
// GET STATS
// ============================================================

$stats = [];

// Users count
$usersResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$usersRow = mysqli_fetch_assoc($usersResult);
$stats['total_users'] = $usersRow ? intval($usersRow['count']) : 0;

// Leads count (check if table exists)
$leadsTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'leads'");
if (mysqli_num_rows($leadsTableCheck) > 0) {
    $leadsResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads");
    $leadsRow = mysqli_fetch_assoc($leadsResult);
    $stats['total_leads'] = $leadsRow ? intval($leadsRow['count']) : 0;
} else {
    $stats['total_leads'] = 0;
}

// Partner Leads count (check if table exists)
$partnerLeadsTableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'partner_leads'");
if (mysqli_num_rows($partnerLeadsTableCheck) > 0) {
    $partnerLeadsResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM partner_leads");
    $partnerLeadsRow = mysqli_fetch_assoc($partnerLeadsResult);
    $stats['total_partner_leads'] = $partnerLeadsRow ? intval($partnerLeadsRow['count']) : 0;
} else {
    $stats['total_partner_leads'] = 0;
}

// Customers count
$customersResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM customers");
$customersRow = mysqli_fetch_assoc($customersResult);
$stats['total_customers'] = $customersRow ? intval($customersRow['count']) : 0;

// Partners count
$partnersResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM partners");
$partnersRow = mysqli_fetch_assoc($partnersResult);
$stats['total_partners'] = $partnersRow ? intval($partnersRow['count']) : 0;

// Sales count
$salesResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM sales");
$salesRow = mysqli_fetch_assoc($salesResult);
$stats['total_sales'] = $salesRow ? intval($salesRow['count']) : 0;

// Total revenue
$revenueResult = mysqli_query($conn, "SELECT SUM(amount) as total FROM sales WHERE status = 'Completed'");
$revenueRow = mysqli_fetch_assoc($revenueResult);
$stats['total_revenue'] = $revenueRow ? floatval($revenueRow['total'] ?? 0) : 0;

// Pending sales count
$pendingResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM sales WHERE status = 'Pending'");
$pendingRow = mysqli_fetch_assoc($pendingResult);
$stats['pending_sales'] = $pendingRow ? intval($pendingRow['count']) : 0;

// Partner applications count
$partnerAppsResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM partner_applications");
$partnerAppsRow = mysqli_fetch_assoc($partnerAppsResult);
$stats['total_partner_applications'] = $partnerAppsRow ? intval($partnerAppsRow['count']) : 0;

// Pending partner applications
$pendingAppsResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM partner_applications WHERE status = 'pending'");
$pendingAppsRow = mysqli_fetch_assoc($pendingAppsResult);
$stats['pending_partner_applications'] = $pendingAppsRow ? intval($pendingAppsRow['count']) : 0;

// Services count
$servicesResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM services");
$servicesRow = mysqli_fetch_assoc($servicesResult);
$stats['total_services'] = $servicesRow ? intval($servicesRow['count']) : 0;

// Reviews count
$reviewsResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM reviews");
$reviewsRow = mysqli_fetch_assoc($reviewsResult);
$stats['total_reviews'] = $reviewsRow ? intval($reviewsRow['count']) : 0;

// Quotations count
$quotationsResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM quotations");
$quotationsRow = mysqli_fetch_assoc($quotationsResult);
$stats['total_quotations'] = $quotationsRow ? intval($quotationsRow['count']) : 0;

// Active users
$activeUsersResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$activeUsersRow = mysqli_fetch_assoc($activeUsersResult);
$stats['active_users'] = $activeUsersRow ? intval($activeUsersRow['count']) : 0;

// ============================================================
# RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Stats retrieved successfully',
    'data' => $stats,
    'generated_at' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>