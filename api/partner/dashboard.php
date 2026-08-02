<?php
// /api/partner/dashboard.php
// Partner Dashboard API - Get dashboard statistics

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Direct database connection
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

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$user_role = $_SESSION['user_role'] ?? '';

if ($user_role !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access - Partner only']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Get stats
$total_leads = 0;
$converted = 0;
$new_leads = 0;
$contacted_leads = 0;
$lost_leads = 0;

// Get leads counts
$query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_cnt,
            SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted_cnt,
            SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost_cnt
          FROM leads WHERE partner_id = $partner_id";
$result = mysqli_query($conn, $query);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $total_leads = (int)$row['total'];
    $converted = (int)$row['converted'];
    $new_leads = (int)$row['new_cnt'];
    $contacted_leads = (int)$row['contacted_cnt'];
    $lost_leads = (int)$row['lost_cnt'];
}

$conversion_rate = $total_leads > 0 ? round(($converted / $total_leads) * 100) : 0;

// Get total commission
$total_commission = 0;
$result = mysqli_query($conn, "SELECT COALESCE(SUM(commission_amount), 0) as total FROM leads WHERE partner_id = $partner_id AND status = 'converted'");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $total_commission = (float)$row['total'];
}

// Get monthly commission (last 7 months)
$monthly_commission = [];
for ($i = 6; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $result = mysqli_query($conn, "SELECT COALESCE(SUM(commission_amount), 0) as total FROM leads WHERE partner_id = $partner_id AND status = 'converted' AND DATE_FORMAT(created_at, '%Y-%m') = '$month'");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $monthly_commission[] = (float)($row['total'] ?? 0);
    } else {
        $monthly_commission[] = 0;
    }
}

// Get recent activities
$activities = [];
$activity_query = "SELECT 'Lead added' as activity, customer_name as customer, created_at as date, status FROM leads WHERE partner_id = $partner_id ORDER BY created_at DESC LIMIT 5";
$activity_result = mysqli_query($conn, $activity_query);
if ($activity_result) {
    while ($row = mysqli_fetch_assoc($activity_result)) {
        $activities[] = [
            'activity' => $row['activity'],
            'customer' => $row['customer'],
            'date' => $row['date'],
            'status' => $row['status'],
            'amount' => null
        ];
    }
}

// Return success response
echo json_encode([
    'success' => true,
    'data' => [
        'total_leads' => $total_leads,
        'converted' => $converted,
        'total_commission' => $total_commission,
        'conversion_rate' => $conversion_rate,
        'new_leads' => $new_leads,
        'contacted_leads' => $contacted_leads,
        'lost_leads' => $lost_leads,
        'monthly_commission' => $monthly_commission,
        'recent_activity' => $activities
    ]
]);

mysqli_close($conn);
?>