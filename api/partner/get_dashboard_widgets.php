<?php
// api/partner/get_widgets.php
// Partner Get Widgets API - Dashboard widgets for partner home page

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

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

// Verify user is actually a partner
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
        echo json_encode(['success' => false, 'error' => 'Leads table not found']);
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

// ========== 1. QUICK STATS WIDGET ==========
// Total leads
$total_query = "SELECT COUNT(*) as total FROM $leadsTable WHERE partner_id = ?";
$total_stmt = mysqli_prepare($conn, $total_query);
mysqli_stmt_bind_param($total_stmt, "i", $partner_id);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_leads = mysqli_fetch_assoc($total_result)['total'] ?? 0;
mysqli_stmt_close($total_stmt);

// Converted leads
$converted_query = "SELECT COUNT(*) as total FROM $leadsTable WHERE partner_id = ? AND $statusCol = 'converted'";
$converted_stmt = mysqli_prepare($conn, $converted_query);
mysqli_stmt_bind_param($converted_stmt, "i", $partner_id);
mysqli_stmt_execute($converted_stmt);
$converted_result = mysqli_stmt_get_result($converted_stmt);
$converted = mysqli_fetch_assoc($converted_result)['total'] ?? 0;
mysqli_stmt_close($converted_stmt);

// Total commission
$commission_query = "SELECT SUM($commissionCol) as total FROM $leadsTable WHERE partner_id = ? AND $statusCol = 'converted'";
$commission_stmt = mysqli_prepare($conn, $commission_query);
mysqli_stmt_bind_param($commission_stmt, "i", $partner_id);
mysqli_stmt_execute($commission_stmt);
$commission_result = mysqli_stmt_get_result($commission_stmt);
$total_commission = mysqli_fetch_assoc($commission_result)['total'] ?? 0;
mysqli_stmt_close($commission_stmt);

// Pending payout from partners table
$pending_payout = 0;
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

// If no pending payout, calculate from unpaid leads
if ($pending_payout == 0) {
    $pending_query = "SELECT SUM($commissionCol) as total FROM $leadsTable WHERE partner_id = ? AND $statusCol = 'converted' AND paid_status != 'paid'";
    $pending_stmt = mysqli_prepare($conn, $pending_query);
    if ($pending_stmt) {
        mysqli_stmt_bind_param($pending_stmt, "i", $partner_id);
        mysqli_stmt_execute($pending_stmt);
        $pending_result = mysqli_stmt_get_result($pending_stmt);
        $pending_data = mysqli_fetch_assoc($pending_result);
        $pending_payout = $pending_data['total'] ?? 0;
        mysqli_stmt_close($pending_stmt);
    }
}

// ========== 2. TODAY'S STATS ==========
$today = date('Y-m-d');
$today_start = $today . ' 00:00:00';
$today_end = $today . ' 23:59:59';

$today_query = "SELECT 
                    COUNT(*) as leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted,
                    SUM(CASE WHEN $statusCol = 'converted' THEN $commissionCol ELSE 0 END) as commission
                 FROM $leadsTable 
                 WHERE partner_id = ? AND created_at BETWEEN ? AND ?";

$today_stmt = mysqli_prepare($conn, $today_query);
mysqli_stmt_bind_param($today_stmt, "iss", $partner_id, $today_start, $today_end);
mysqli_stmt_execute($today_stmt);
$today_result = mysqli_stmt_get_result($today_stmt);
$today_stats = mysqli_fetch_assoc($today_result);
mysqli_stmt_close($today_stmt);

// ========== 3. RECENT ACTIVITIES ==========
$activities = [];
$checkActivitiesTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
if (mysqli_num_rows($checkActivitiesTable) > 0) {
    $activity_query = "SELECT activity_type, description, DATE_FORMAT(created_at, '%d-%m-%Y %h:%i %p') as time
                       FROM activities 
                       WHERE user_id = ? 
                       ORDER BY created_at DESC 
                       LIMIT 10";
    
    $activity_stmt = mysqli_prepare($conn, $activity_query);
    mysqli_stmt_bind_param($activity_stmt, "i", $partner_id);
    mysqli_stmt_execute($activity_stmt);
    $activity_result = mysqli_stmt_get_result($activity_stmt);
    $activities = mysqli_fetch_all($activity_result, MYSQLI_ASSOC);
    mysqli_stmt_close($activity_stmt);
}

// ========== 4. RECENT LEADS ==========
$recent_query = "SELECT id, customer_name, customer_phone, $statusCol as status, DATE_FORMAT(created_at, '%d-%m-%Y') as date
                 FROM $leadsTable 
                 WHERE partner_id = ? 
                 ORDER BY id DESC 
                 LIMIT 10";

$recent_stmt = mysqli_prepare($conn, $recent_query);
mysqli_stmt_bind_param($recent_stmt, "i", $partner_id);
mysqli_stmt_execute($recent_stmt);
$recent_result = mysqli_stmt_get_result($recent_stmt);
$recent_leads = mysqli_fetch_all($recent_result, MYSQLI_ASSOC);
mysqli_stmt_close($recent_stmt);

// ========== 5. PENDING TASKS ==========
$pending_tasks = [];
$pending_count = 0;

// Pending leads that need follow-up (older than 2 days, status 'new')
$pending_leads_query = "SELECT COUNT(*) as count FROM $leadsTable 
                        WHERE partner_id = ? AND $statusCol = 'new' AND created_at < DATE_SUB(NOW(), INTERVAL 2 DAY)";
$pending_leads_stmt = mysqli_prepare($conn, $pending_leads_query);
mysqli_stmt_bind_param($pending_leads_stmt, "i", $partner_id);
mysqli_stmt_execute($pending_leads_stmt);
$pending_leads_result = mysqli_stmt_get_result($pending_leads_stmt);
$pending_leads_data = mysqli_fetch_assoc($pending_leads_result);
$pending_leads_count = $pending_leads_data['count'] ?? 0;
mysqli_stmt_close($pending_leads_stmt);

if ($pending_leads_count > 0) {
    $pending_tasks[] = [
        'task' => 'Follow-up on ' . $pending_leads_count . ' pending lead(s)',
        'priority' => 'high',
        'icon' => 'fa-phone'
    ];
    $pending_count += $pending_leads_count;
}

// Pending payout requests
$checkPayoutsTable = mysqli_query($conn, "SHOW TABLES LIKE 'partner_payouts'");
if (mysqli_num_rows($checkPayoutsTable) > 0) {
    $pending_payouts_query = "SELECT COUNT(*) as count FROM partner_payouts 
                              WHERE partner_id = ? AND status = 'pending'";
    $pending_payouts_stmt = mysqli_prepare($conn, $pending_payouts_query);
    mysqli_stmt_bind_param($pending_payouts_stmt, "i", $partner_id);
    mysqli_stmt_execute($pending_payouts_stmt);
    $pending_payouts_result = mysqli_stmt_get_result($pending_payouts_stmt);
    $payout_data = mysqli_fetch_assoc($pending_payouts_result);
    $pending_payouts_count = $payout_data['count'] ?? 0;
    mysqli_stmt_close($pending_payouts_stmt);
    
    if ($pending_payouts_count > 0) {
        $pending_tasks[] = [
            'task' => $pending_payouts_count . ' payout request(s) pending',
            'priority' => 'medium',
            'icon' => 'fa-wallet'
        ];
        $pending_count += $pending_payouts_count;
    }
}

// Unread notifications
$checkNotificationsTable = mysqli_query($conn, "SHOW TABLES LIKE 'partner_notifications'");
if (mysqli_num_rows($checkNotificationsTable) > 0) {
    $unread_query = "SELECT COUNT(*) as count FROM partner_notifications 
                     WHERE partner_id = ? AND is_read = 0";
    $unread_stmt = mysqli_prepare($conn, $unread_query);
    mysqli_stmt_bind_param($unread_stmt, "i", $partner_id);
    mysqli_stmt_execute($unread_stmt);
    $unread_result = mysqli_stmt_get_result($unread_stmt);
    $notify_data = mysqli_fetch_assoc($unread_result);
    $unread_count = $notify_data['count'] ?? 0;
    mysqli_stmt_close($unread_stmt);
    
    if ($unread_count > 0) {
        $pending_tasks[] = [
            'task' => $unread_count . ' unread notification(s)',
            'priority' => 'low',
            'icon' => 'fa-bell'
        ];
        $pending_count += $unread_count;
    }
}

// ========== 6. DAILY TIP ==========
$tips = [
    "Follow up with leads within 24 hours for better conversion rates",
    "Share your referral link on social media to earn more commission",
    "Update your bank details to receive payouts faster",
    "Respond to customer queries within 1 hour for better satisfaction",
    "Track your performance weekly to identify improvement areas",
    "Converted leads earn you 10% commission on every successful sale",
    "Use the AI analyzer to get insights from customer documents",
    "Bulk import leads to save time and increase productivity",
    "Set daily targets to achieve your monthly goals",
    "Review your analytics to understand what's working best"
];
$random_tip = $tips[array_rand($tips)];

// ========== 7. GOAL PROGRESS WIDGET ==========
$monthly_target = 20; // Can be dynamic based on partner level from database

// Get current month leads
$current_month_query = "SELECT COUNT(*) as leads FROM $leadsTable 
                        WHERE partner_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                        AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$current_month_stmt = mysqli_prepare($conn, $current_month_query);
mysqli_stmt_bind_param($current_month_stmt, "i", $partner_id);
mysqli_stmt_execute($current_month_stmt);
$current_month_result = mysqli_stmt_get_result($current_month_stmt);
$current_month_data = mysqli_fetch_assoc($current_month_result);
$current_month_leads = $current_month_data['leads'] ?? 0;
mysqli_stmt_close($current_month_stmt);

// Get previous month leads for comparison
$prev_month_query = "SELECT COUNT(*) as leads FROM $leadsTable 
                     WHERE partner_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) 
                     AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)";
$prev_month_stmt = mysqli_prepare($conn, $prev_month_query);
mysqli_stmt_bind_param($prev_month_stmt, "i", $partner_id);
mysqli_stmt_execute($prev_month_stmt);
$prev_month_result = mysqli_stmt_get_result($prev_month_stmt);
$prev_month_data = mysqli_fetch_assoc($prev_month_result);
$prev_month_leads = $prev_month_data['leads'] ?? 0;
mysqli_stmt_close($prev_month_stmt);

// Calculate growth
$monthly_growth = 0;
if ($prev_month_leads > 0) {
    $monthly_growth = round((($current_month_leads - $prev_month_leads) / $prev_month_leads) * 100, 2);
}

$goal_progress = min(round(($current_month_leads / $monthly_target) * 100, 0), 100);

// ========== 8. ACHIEVEMENTS/BADGES ==========
$achievements = [];
$checkAchievementsTable = mysqli_query($conn, "SHOW TABLES LIKE 'partner_achievements'");
if (mysqli_num_rows($checkAchievementsTable) > 0) {
    $achievement_query = "SELECT title, description, icon, earned_at FROM partner_achievements 
                          WHERE partner_id = ? AND is_earned = 1 
                          ORDER BY earned_at DESC LIMIT 3";
    $achievement_stmt = mysqli_prepare($conn, $achievement_query);
    mysqli_stmt_bind_param($achievement_stmt, "i", $partner_id);
    mysqli_stmt_execute($achievement_stmt);
    $achievement_result = mysqli_stmt_get_result($achievement_stmt);
    $achievements = mysqli_fetch_all($achievement_result, MYSQLI_ASSOC);
    mysqli_stmt_close($achievement_stmt);
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'partner' => [
        'id' => $partner_id,
        'name' => $partner_name
    ],
    'widgets' => [
        'quick_stats' => [
            'total_leads' => (int)$total_leads,
            'converted' => (int)$converted,
            'total_commission' => round((float)$total_commission, 2),
            'pending_payout' => round((float)$pending_payout, 2),
            'conversion_rate' => $total_leads > 0 ? round(($converted / $total_leads) * 100, 2) : 0
        ],
        'today_stats' => [
            'leads' => (int)($today_stats['leads'] ?? 0),
            'converted' => (int)($today_stats['converted'] ?? 0),
            'commission' => round((float)($today_stats['commission'] ?? 0), 2),
            'conversion_rate' => ($today_stats['leads'] ?? 0) > 0 
                ? round((($today_stats['converted'] ?? 0) / ($today_stats['leads'] ?? 0)) * 100, 2) 
                : 0
        ],
        'recent_activities' => $activities,
        'recent_leads' => $recent_leads,
        'pending_tasks' => $pending_tasks,
        'pending_count' => $pending_count,
        'daily_tip' => $random_tip,
        'goal_progress' => [
            'current' => (int)$current_month_leads,
            'target' => $monthly_target,
            'percentage' => $goal_progress,
            'remaining' => max(0, $monthly_target - $current_month_leads),
            'growth' => $monthly_growth
        ],
        'achievements' => $achievements
    ],
    'last_updated' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>