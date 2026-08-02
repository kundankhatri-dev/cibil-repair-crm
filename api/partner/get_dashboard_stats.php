<?php
// api/partner/get_dashboard_stats.php
// Partner Dashboard Stats API - Get real-time statistics for partner dashboard

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config - FIXED PATH (config.php is in the same folder)
require_once __DIR__ . '/config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner and get name
$role_check = mysqli_prepare($conn, "SELECT role, name FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$partner_name = $role_data['name'];

// ========== DETERMINE LEADS TABLE ==========
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
    $checkTable2 = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
    if (mysqli_num_rows($checkTable2) == 0) {
        echo json_encode([
            'success' => false, 
            'error' => 'Leads table not found. Please contact support.',
            'debug' => 'Missing leads table'
        ]);
        exit;
    }
}

// ========== GET TABLE COLUMN NAMES ==========
$columns = [];
$colResult = mysqli_query($conn, "SHOW COLUMNS FROM $leadsTable");
if ($colResult) {
    while ($col = mysqli_fetch_assoc($colResult)) {
        $columns[] = $col['Field'];
    }
}

$commissionCol = in_array('commission_amount', $columns) ? 'commission_amount' : 'commission_amount';
$statusCol = in_array('status', $columns) ? 'status' : 'status';

// ========== DATE CALCULATIONS ==========
$today = date('Y-m-d');
$today_start = $today . ' 00:00:00';
$today_end = $today . ' 23:59:59';

// Week start (Monday) - fixed
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$week_start_datetime = $week_start . ' 00:00:00';
$week_end_datetime = $week_end . ' 23:59:59';

// Month start
$month_start = date('Y-m-01') . ' 00:00:00';
$month_end = date('Y-m-t') . ' 23:59:59';

// Previous month for comparison
$prev_month_start = date('Y-m-01', strtotime('-1 month')) . ' 00:00:00';
$prev_month_end = date('Y-m-t', strtotime('-1 month')) . ' 23:59:59';

// ========== 1. TODAY'S STATS ==========
$today_query = "SELECT 
                    COUNT(*) as today_leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as today_converted,
                    SUM(CASE WHEN $statusCol = 'converted' THEN $commissionCol ELSE 0 END) as today_commission
                 FROM $leadsTable 
                 WHERE partner_id = ? AND created_at BETWEEN ? AND ?";

$today_stmt = mysqli_prepare($conn, $today_query);
mysqli_stmt_bind_param($today_stmt, "iss", $partner_id, $today_start, $today_end);
mysqli_stmt_execute($today_stmt);
$today_result = mysqli_stmt_get_result($today_stmt);
$today_stats = mysqli_fetch_assoc($today_result);
mysqli_stmt_close($today_stmt);

// ========== 2. WEEKLY STATS ==========
$week_query = "SELECT 
                    COUNT(*) as week_leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as week_converted,
                    SUM(CASE WHEN $statusCol = 'converted' THEN $commissionCol ELSE 0 END) as week_commission
                 FROM $leadsTable 
                 WHERE partner_id = ? AND created_at BETWEEN ? AND ?";

$week_stmt = mysqli_prepare($conn, $week_query);
mysqli_stmt_bind_param($week_stmt, "iss", $partner_id, $week_start_datetime, $week_end_datetime);
mysqli_stmt_execute($week_stmt);
$week_result = mysqli_stmt_get_result($week_stmt);
$week_stats = mysqli_fetch_assoc($week_result);
mysqli_stmt_close($week_stmt);

// ========== 3. MONTHLY STATS ==========
$month_query = "SELECT 
                    COUNT(*) as month_leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as month_converted,
                    SUM(CASE WHEN $statusCol = 'converted' THEN $commissionCol ELSE 0 END) as month_commission
                 FROM $leadsTable 
                 WHERE partner_id = ? AND created_at BETWEEN ? AND ?";

$month_stmt = mysqli_prepare($conn, $month_query);
mysqli_stmt_bind_param($month_stmt, "iss", $partner_id, $month_start, $month_end);
mysqli_stmt_execute($month_stmt);
$month_result = mysqli_stmt_get_result($month_stmt);
$month_stats = mysqli_fetch_assoc($month_result);
mysqli_stmt_close($month_stmt);

// ========== 4. PREVIOUS MONTH STATS (for growth calculation) ==========
$prev_month_query = "SELECT 
                        COUNT(*) as prev_leads,
                        SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as prev_converted,
                        SUM(CASE WHEN $statusCol = 'converted' THEN $commissionCol ELSE 0 END) as prev_commission
                      FROM $leadsTable 
                      WHERE partner_id = ? AND created_at BETWEEN ? AND ?";

$prev_month_stmt = mysqli_prepare($conn, $prev_month_query);
mysqli_stmt_bind_param($prev_month_stmt, "iss", $partner_id, $prev_month_start, $prev_month_end);
mysqli_stmt_execute($prev_month_stmt);
$prev_month_result = mysqli_stmt_get_result($prev_month_stmt);
$prev_month_stats = mysqli_fetch_assoc($prev_month_result);
mysqli_stmt_close($prev_month_stmt);

// ========== 5. TOTAL STATS (from leads table) ==========
$total_query = "SELECT 
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as total_converted,
                    SUM(CASE WHEN $statusCol = 'converted' THEN $commissionCol ELSE 0 END) as total_commission
                 FROM $leadsTable 
                 WHERE partner_id = ?";

$total_stmt = mysqli_prepare($conn, $total_query);
mysqli_stmt_bind_param($total_stmt, "i", $partner_id);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_stats = mysqli_fetch_assoc($total_result);
mysqli_stmt_close($total_stmt);

// ========== 6. GET PENDING PAYOUT ==========
$pending_payout = 0;

// Try from partners table first
$checkPartnersTable = mysqli_query($conn, "SHOW TABLES LIKE 'partners'");
if (mysqli_num_rows($checkPartnersTable) > 0) {
    $payout_query = "SELECT pending_payout FROM partners WHERE user_id = ?";
    $payout_stmt = mysqli_prepare($conn, $payout_query);
    if ($payout_stmt) {
        mysqli_stmt_bind_param($payout_stmt, "i", $partner_id);
        mysqli_stmt_execute($payout_stmt);
        $payout_result = mysqli_stmt_get_result($payout_stmt);
        $payout_data = mysqli_fetch_assoc($payout_result);
        $pending_payout = $payout_data['pending_payout'] ?? 0;
        mysqli_stmt_close($payout_stmt);
    }
}

// If no pending payout found, calculate from unpaid commissions
if ($pending_payout == 0) {
    // Check if paid_status column exists
    $checkPaidStatus = in_array('paid_status', $columns);
    if ($checkPaidStatus) {
        $pending_query = "SELECT SUM($commissionCol) as pending_total 
                          FROM $leadsTable 
                          WHERE partner_id = ? AND $statusCol = 'converted' AND paid_status != 'paid'";
        $pending_stmt = mysqli_prepare($conn, $pending_query);
        if ($pending_stmt) {
            mysqli_stmt_bind_param($pending_stmt, "i", $partner_id);
            mysqli_stmt_execute($pending_stmt);
            $pending_result = mysqli_stmt_get_result($pending_stmt);
            $pending_data = mysqli_fetch_assoc($pending_result);
            $pending_payout = $pending_data['pending_total'] ?? 0;
            mysqli_stmt_close($pending_stmt);
        }
    }
}

// ========== 7. CALCULATE CONVERSION RATES ==========
$today_conversion_rate = ($today_stats['today_leads'] > 0) 
    ? round(($today_stats['today_converted'] / $today_stats['today_leads']) * 100, 2) 
    : 0;

$week_conversion_rate = ($week_stats['week_leads'] > 0) 
    ? round(($week_stats['week_converted'] / $week_stats['week_leads']) * 100, 2) 
    : 0;

$month_conversion_rate = ($month_stats['month_leads'] > 0) 
    ? round(($month_stats['month_converted'] / $month_stats['month_leads']) * 100, 2) 
    : 0;

$total_conversion_rate = ($total_stats['total_leads'] > 0) 
    ? round(($total_stats['total_converted'] / $total_stats['total_leads']) * 100, 2) 
    : 0;

// ========== 8. CALCULATE GROWTH PERCENTAGES ==========
$monthly_growth = 0;
$monthly_commission_growth = 0;

if ($prev_month_stats['prev_leads'] > 0) {
    $monthly_growth = round((($month_stats['month_leads'] - $prev_month_stats['prev_leads']) / $prev_month_stats['prev_leads']) * 100, 2);
    $monthly_commission_growth = round((($month_stats['month_commission'] - $prev_month_stats['prev_commission']) / $prev_month_stats['prev_commission']) * 100, 2);
}

// ========== 9. GET LAST 7 DAYS ACTIVITY ==========
$activity_query = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted
                  FROM $leadsTable 
                  WHERE partner_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY date ASC";

$activity_stmt = mysqli_prepare($conn, $activity_query);
mysqli_stmt_bind_param($activity_stmt, "i", $partner_id);
mysqli_stmt_execute($activity_stmt);
$activity_result = mysqli_stmt_get_result($activity_stmt);
$weekly_activity = mysqli_fetch_all($activity_result, MYSQLI_ASSOC);
mysqli_stmt_close($activity_stmt);

// Fill missing dates
$last_7_days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $found = false;
    foreach ($weekly_activity as $activity) {
        if ($activity['date'] == $date) {
            $last_7_days[] = [
                'date' => $date,
                'day' => date('D', strtotime($date)),
                'leads' => (int)$activity['leads'],
                'converted' => (int)$activity['converted']
            ];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $last_7_days[] = [
            'date' => $date,
            'day' => date('D', strtotime($date)),
            'leads' => 0,
            'converted' => 0
        ];
    }
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'partner' => [
        'id' => $partner_id,
        'name' => $partner_name
    ],
    'today' => [
        'leads' => (int)($today_stats['today_leads'] ?? 0),
        'converted' => (int)($today_stats['today_converted'] ?? 0),
        'commission' => (float)($today_stats['today_commission'] ?? 0),
        'conversion_rate' => $today_conversion_rate
    ],
    'week' => [
        'leads' => (int)($week_stats['week_leads'] ?? 0),
        'converted' => (int)($week_stats['week_converted'] ?? 0),
        'commission' => (float)($week_stats['week_commission'] ?? 0),
        'conversion_rate' => $week_conversion_rate,
        'start_date' => $week_start,
        'end_date' => $week_end
    ],
    'month' => [
        'leads' => (int)($month_stats['month_leads'] ?? 0),
        'converted' => (int)($month_stats['month_converted'] ?? 0),
        'commission' => (float)($month_stats['month_commission'] ?? 0),
        'conversion_rate' => $month_conversion_rate,
        'growth' => $monthly_growth,
        'commission_growth' => $monthly_commission_growth,
        'start_date' => $month_start,
        'end_date' => $month_end
    ],
    'total' => [
        'leads' => (int)($total_stats['total_leads'] ?? 0),
        'converted' => (int)($total_stats['total_converted'] ?? 0),
        'commission' => (float)($total_stats['total_commission'] ?? 0),
        'conversion_rate' => $total_conversion_rate,
        'pending_payout' => (float)$pending_payout
    ],
    'weekly_activity' => $last_7_days,
    'last_updated' => date('Y-m-d H:i:s')
]);

// ========== CLEAN UP ==========
if (isset($role_check)) mysqli_stmt_close($role_check);
if (isset($today_stmt)) mysqli_stmt_close($today_stmt);
if (isset($week_stmt)) mysqli_stmt_close($week_stmt);
if (isset($month_stmt)) mysqli_stmt_close($month_stmt);
if (isset($prev_month_stmt)) mysqli_stmt_close($prev_month_stmt);
if (isset($total_stmt)) mysqli_stmt_close($total_stmt);
if (isset($payout_stmt)) mysqli_stmt_close($payout_stmt);
if (isset($pending_stmt)) mysqli_stmt_close($pending_stmt);
if (isset($activity_stmt)) mysqli_stmt_close($activity_stmt);

mysqli_close($conn);
?>