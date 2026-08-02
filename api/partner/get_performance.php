<?php
// api/partner/get_performance_analytics.php
// Partner Get Performance Analytics API - Comprehensive performance metrics

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

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
$serviceCol = in_array('service_type', $columns) ? 'service_type' : (in_array('service', $columns) ? 'service' : 'service_type');

// ========== 1. MONTHLY PERFORMANCE (Last 6 months) ==========
$monthly_query = "SELECT 
                    DATE_FORMAT(created_at, '%b') as month,
                    DATE_FORMAT(created_at, '%Y-%m') as month_key,
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted,
                    SUM(CASE WHEN $statusCol = 'converted' THEN $commissionCol ELSE 0 END) as total_commission,
                    ROUND((SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2) as conversion_rate
                  FROM $leadsTable 
                  WHERE partner_id = ? 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY created_at ASC";

$stmt = mysqli_prepare($conn, $monthly_query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$monthly_performance = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Calculate month-over-month growth
foreach ($monthly_performance as $i => &$month) {
    if ($i > 0) {
        $prev_total = $monthly_performance[$i-1]['total_leads'];
        $prev_converted = $monthly_performance[$i-1]['converted'];
        $month['lead_growth'] = $prev_total > 0 ? round((($month['total_leads'] - $prev_total) / $prev_total) * 100, 2) : 0;
        $month['conversion_growth'] = $prev_converted > 0 ? round((($month['converted'] - $prev_converted) / $prev_converted) * 100, 2) : 0;
    } else {
        $month['lead_growth'] = 0;
        $month['conversion_growth'] = 0;
    }
    $month['total_commission'] = (float)$month['total_commission'];
}

// ========== 2. WEEKLY PERFORMANCE (Last 4 weeks) ==========
$weekly_query = "SELECT 
                  WEEK(created_at, 1) as week_num,
                  DATE_FORMAT(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY), '%d %b') as week_start,
                  COUNT(*) as total_leads,
                  SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted,
                  SUM(CASE WHEN $statusCol = 'converted' THEN $commissionCol ELSE 0 END) as total_commission,
                  ROUND((SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2) as conversion_rate
                FROM $leadsTable 
                WHERE partner_id = ? 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
                GROUP BY WEEK(created_at, 1)
                ORDER BY created_at ASC";

$stmt2 = mysqli_prepare($conn, $weekly_query);
mysqli_stmt_bind_param($stmt2, "i", $partner_id);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);
$weekly_performance = mysqli_fetch_all($result2, MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);

// ========== 3. CONVERSION FUNNEL STATS ==========
$conversion_query = "SELECT 
                      COUNT(*) as total_leads,
                      SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted,
                      SUM(CASE WHEN $statusCol = 'lost' THEN 1 ELSE 0 END) as lost,
                      SUM(CASE WHEN $statusCol = 'contacted' THEN 1 ELSE 0 END) as contacted,
                      SUM(CASE WHEN $statusCol = 'new' THEN 1 ELSE 0 END) as new_leads,
                      SUM(CASE WHEN $statusCol = 'converted' THEN $commissionCol ELSE 0 END) as total_commission
                    FROM $leadsTable 
                    WHERE partner_id = ?";

$stmt3 = mysqli_prepare($conn, $conversion_query);
mysqli_stmt_bind_param($stmt3, "i", $partner_id);
mysqli_stmt_execute($stmt3);
$result3 = mysqli_stmt_get_result($stmt3);
$conversion_stats = mysqli_fetch_assoc($result3);
mysqli_stmt_close($stmt3);

$total = max($conversion_stats['total_leads'] ?? 1, 1);
$conversion_rate = round(($conversion_stats['converted'] / $total) * 100, 2);

// ========== 4. DAILY AVERAGE PERFORMANCE ==========
$daily_avg_query = "SELECT 
                      AVG(daily_leads) as avg_daily_leads,
                      AVG(daily_converted) as avg_daily_converted,
                      MAX(daily_leads) as max_daily_leads,
                      MIN(daily_leads) as min_daily_leads
                    FROM (
                      SELECT DATE(created_at) as date,
                             COUNT(*) as daily_leads,
                             SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as daily_converted
                      FROM $leadsTable 
                      WHERE partner_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                      GROUP BY DATE(created_at)
                    ) as daily_stats";

$daily_stmt = mysqli_prepare($conn, $daily_avg_query);
mysqli_stmt_bind_param($daily_stmt, "i", $partner_id);
mysqli_stmt_execute($daily_stmt);
$daily_result = mysqli_stmt_get_result($daily_stmt);
$daily_avg = mysqli_fetch_assoc($daily_result);
mysqli_stmt_close($daily_stmt);

// ========== 5. MONTHLY TARGET PROGRESS ==========
// Dynamic target based on previous month performance
$prev_month_query = "SELECT COUNT(*) as prev_leads FROM $leadsTable 
                     WHERE partner_id = ? 
                     AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)
                     AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)";
$prev_stmt = mysqli_prepare($conn, $prev_month_query);
mysqli_stmt_bind_param($prev_stmt, "i", $partner_id);
mysqli_stmt_execute($prev_stmt);
$prev_result = mysqli_stmt_get_result($prev_stmt);
$prev_data = mysqli_fetch_assoc($prev_result);
$prev_month_leads = $prev_data['prev_leads'] ?? 0;
mysqli_stmt_close($prev_stmt);

// Set target: 20% higher than previous month, minimum 10, maximum 100
$monthly_target = max(10, min(100, round($prev_month_leads * 1.2)));

$current_month_query = "SELECT COUNT(*) as leads FROM $leadsTable 
                        WHERE partner_id = ? 
                        AND MONTH(created_at) = MONTH(CURRENT_DATE())
                        AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$current_stmt = mysqli_prepare($conn, $current_month_query);
mysqli_stmt_bind_param($current_stmt, "i", $partner_id);
mysqli_stmt_execute($current_stmt);
$current_result = mysqli_stmt_get_result($current_stmt);
$current_data = mysqli_fetch_assoc($current_result);
$current_month_leads = $current_data['leads'] ?? 0;
mysqli_stmt_close($current_stmt);

$target_progress = min(round(($current_month_leads / max($monthly_target, 1)) * 100, 2), 100);
$remaining = max(0, $monthly_target - $current_month_leads);

// ========== 6. BEST PERFORMING SERVICE ==========
$service_query = "SELECT 
                    $serviceCol as service,
                    COUNT(*) as total,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted,
                    ROUND((SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2) as conversion_rate,
                    SUM(CASE WHEN $statusCol = 'converted' THEN $commissionCol ELSE 0 END) as total_commission
                  FROM $leadsTable 
                  WHERE partner_id = ? AND $serviceCol IS NOT NULL
                  GROUP BY $serviceCol
                  ORDER BY converted DESC, conversion_rate DESC";

$stmt4 = mysqli_prepare($conn, $service_query);
mysqli_stmt_bind_param($stmt4, "i", $partner_id);
mysqli_stmt_execute($stmt4);
$result4 = mysqli_stmt_get_result($stmt4);
$service_performance = mysqli_fetch_all($result4, MYSQLI_ASSOC);
mysqli_stmt_close($stmt4);

$best_service = !empty($service_performance) ? $service_performance[0] : null;

// ========== 7. AVERAGE RESPONSE TIME ==========
// Average time from lead creation to conversion
$avg_time_query = "SELECT AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_days_to_convert
                   FROM $leadsTable 
                   WHERE partner_id = ? AND $statusCol = 'converted'";
$avg_time_stmt = mysqli_prepare($conn, $avg_time_query);
$avg_conversion_days = 0;
if ($avg_time_stmt) {
    mysqli_stmt_bind_param($avg_time_stmt, "i", $partner_id);
    mysqli_stmt_execute($avg_time_stmt);
    $avg_time_result = mysqli_stmt_get_result($avg_time_stmt);
    $avg_time_data = mysqli_fetch_assoc($avg_time_result);
    $avg_conversion_days = round($avg_time_data['avg_days_to_convert'] ?? 0, 1);
    mysqli_stmt_close($avg_time_stmt);
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'partner' => [
        'id' => $partner_id,
        'name' => $partner_name
    ],
    'monthly_performance' => $monthly_performance,
    'weekly_performance' => $weekly_performance,
    'conversion_stats' => [
        'total_leads' => (int)($conversion_stats['total_leads'] ?? 0),
        'converted' => (int)($conversion_stats['converted'] ?? 0),
        'lost' => (int)($conversion_stats['lost'] ?? 0),
        'contacted' => (int)($conversion_stats['contacted'] ?? 0),
        'new_leads' => (int)($conversion_stats['new_leads'] ?? 0),
        'total_commission' => round((float)($conversion_stats['total_commission'] ?? 0), 2),
        'conversion_rate' => $conversion_rate,
        'success_rate' => $conversion_rate
    ],
    'daily_average' => [
        'avg_daily_leads' => round((float)($daily_avg['avg_daily_leads'] ?? 0), 1),
        'avg_daily_converted' => round((float)($daily_avg['avg_daily_converted'] ?? 0), 1),
        'best_day_leads' => (int)($daily_avg['max_daily_leads'] ?? 0),
        'worst_day_leads' => (int)($daily_avg['min_daily_leads'] ?? 0)
    ],
    'target_progress' => [
        'current_leads' => (int)$current_month_leads,
        'monthly_target' => $monthly_target,
        'percentage' => $target_progress,
        'remaining' => $remaining,
        'previous_month_leads' => (int)$prev_month_leads,
        'growth_from_previous' => $prev_month_leads > 0 ? round((($current_month_leads - $prev_month_leads) / $prev_month_leads) * 100, 2) : 0
    ],
    'service_performance' => $service_performance,
    'best_performing_service' => $best_service ? [
        'service' => $best_service['service'],
        'total' => (int)$best_service['total'],
        'converted' => (int)$best_service['converted'],
        'conversion_rate' => (float)($best_service['conversion_rate'] ?? 0)
    ] : null,
    'avg_conversion_days' => $avg_conversion_days,
    'performance_score' => calculatePerformanceScore($conversion_rate, $current_month_leads, $monthly_target),
    'last_updated' => date('Y-m-d H:i:s')
]);

// ========== HELPER FUNCTION ==========
function calculatePerformanceScore($conversion_rate, $current_leads, $target) {
    $conv_score = min(100, $conversion_rate * 2); // 50% = 100 points
    $target_score = min(100, ($current_leads / max($target, 1)) * 100);
    return round(($conv_score * 0.4) + ($target_score * 0.6), 2);
}

mysqli_close($conn);
?>