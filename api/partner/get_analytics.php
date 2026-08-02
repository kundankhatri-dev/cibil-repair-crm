<?php
// api/partner/get_analytics.php
// Partner Get Analytics API - Advanced business intelligence and performance analytics

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

// Determine column names
$sourceCol = in_array('source', $columns) ? 'source' : 'source';
$serviceCol = in_array('service_type', $columns) ? 'service_type' : (in_array('service', $columns) ? 'service' : 'service_type');
$commissionCol = in_array('commission_amount', $columns) ? 'commission_amount' : 'commission_amount';
$statusCol = in_array('status', $columns) ? 'status' : 'status';

// ========== GET FILTER PARAMETERS ==========
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$period = isset($_GET['period']) ? $_GET['period'] : '30days'; // 7days, 30days, 90days, custom

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) {
    $from_date = date('Y-m-d', strtotime('-30 days'));
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
    $to_date = date('Y-m-d');
}
if (strtotime($from_date) > strtotime($to_date)) {
    $temp = $from_date;
    $from_date = $to_date;
    $to_date = $temp;
}

// ========== 1. LEAD SOURCE PERFORMANCE ==========
$source_performance = [];
if (in_array($sourceCol, $columns)) {
    $source_query = "SELECT 
                        $sourceCol as source,
                        COUNT(*) as total,
                        SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted,
                        ROUND((SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2) as conversion_rate
                      FROM $leadsTable 
                      WHERE partner_id = ? AND $sourceCol IS NOT NULL AND DATE(created_at) BETWEEN ? AND ?
                      GROUP BY $sourceCol
                      ORDER BY converted DESC";
    
    $source_stmt = mysqli_prepare($conn, $source_query);
    mysqli_stmt_bind_param($source_stmt, "iss", $partner_id, $from_date, $to_date);
    mysqli_stmt_execute($source_stmt);
    $source_result = mysqli_stmt_get_result($source_stmt);
    $source_performance = mysqli_fetch_all($source_result, MYSQLI_ASSOC);
    mysqli_stmt_close($source_stmt);
}

// ========== 2. SERVICE PERFORMANCE ==========
$service_performance = [];
if (in_array($serviceCol, $columns)) {
    $service_query = "SELECT 
                        $serviceCol as service,
                        COUNT(*) as total,
                        SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted,
                        SUM(COALESCE($commissionCol, 0)) as total_commission,
                        ROUND((SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2) as conversion_rate
                      FROM $leadsTable 
                      WHERE partner_id = ? AND $serviceCol IS NOT NULL AND DATE(created_at) BETWEEN ? AND ?
                      GROUP BY $serviceCol
                      ORDER BY converted DESC
                      LIMIT 10";
    
    $service_stmt = mysqli_prepare($conn, $service_query);
    mysqli_stmt_bind_param($service_stmt, "iss", $partner_id, $from_date, $to_date);
    mysqli_stmt_execute($service_stmt);
    $service_result = mysqli_stmt_get_result($service_stmt);
    $service_performance = mysqli_fetch_all($service_result, MYSQLI_ASSOC);
    mysqli_stmt_close($service_stmt);
}

// ========== 3. DAILY PERFORMANCE ==========
$daily_query = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted,
                    SUM(COALESCE($commissionCol, 0)) as commission
                  FROM $leadsTable 
                  WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ?
                  GROUP BY DATE(created_at)
                  ORDER BY date ASC";
    
$daily_stmt = mysqli_prepare($conn, $daily_query);
mysqli_stmt_bind_param($daily_stmt, "iss", $partner_id, $from_date, $to_date);
mysqli_stmt_execute($daily_stmt);
$daily_result = mysqli_stmt_get_result($daily_stmt);
$daily_performance = mysqli_fetch_all($daily_result, MYSQLI_ASSOC);
mysqli_stmt_close($daily_stmt);

// ========== 4. HOURLY PERFORMANCE (Best time to work) ==========
$hourly_query = "SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted,
                    ROUND((SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2) as conversion_rate
                  FROM $leadsTable 
                  WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ?
                  GROUP BY HOUR(created_at)
                  ORDER BY hour ASC";
    
$hourly_stmt = mysqli_prepare($conn, $hourly_query);
mysqli_stmt_bind_param($hourly_stmt, "iss", $partner_id, $from_date, $to_date);
mysqli_stmt_execute($hourly_stmt);
$hourly_result = mysqli_stmt_get_result($hourly_stmt);
$hourly_performance = mysqli_fetch_all($hourly_result, MYSQLI_ASSOC);
mysqli_stmt_close($hourly_stmt);

// ========== 5. GROWTH METRICS ==========
$growth_query = "SELECT 
                    DATE_FORMAT(created_at, '%b %Y') as month,
                    DATE_FORMAT(created_at, '%Y-%m') as month_sort,
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as total_converted,
                    SUM(COALESCE($commissionCol, 0)) as total_commission
                  FROM $leadsTable 
                  WHERE partner_id = ? 
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY created_at ASC
                  LIMIT 12";
    
$growth_stmt = mysqli_prepare($conn, $growth_query);
mysqli_stmt_bind_param($growth_stmt, "i", $partner_id);
mysqli_stmt_execute($growth_stmt);
$growth_result = mysqli_stmt_get_result($growth_stmt);
$growth_metrics = mysqli_fetch_all($growth_result, MYSQLI_ASSOC);
mysqli_stmt_close($growth_stmt);

// Calculate growth percentages
$growth_data = [];
$prev_leads = null;
$prev_converted = null;

foreach ($growth_metrics as $index => $metric) {
    $leads = (int)$metric['total_leads'];
    $converted = (int)$metric['total_converted'];
    
    $lead_growth = null;
    $conversion_growth = null;
    
    if ($prev_leads !== null && $prev_leads > 0) {
        $lead_growth = round((($leads - $prev_leads) / $prev_leads) * 100, 2);
    }
    if ($prev_converted !== null && $prev_converted > 0) {
        $conversion_growth = round((($converted - $prev_converted) / $prev_converted) * 100, 2);
    }
    
    $growth_data[] = [
        'month' => $metric['month'],
        'month_sort' => $metric['month_sort'],
        'leads' => $leads,
        'converted' => $converted,
        'commission' => (float)$metric['total_commission'],
        'lead_growth' => $lead_growth,
        'conversion_growth' => $conversion_growth
    ];
    
    $prev_leads = $leads;
    $prev_converted = $converted;
}

// ========== 6. WEEKDAY PERFORMANCE ==========
$weekday_query = "SELECT 
                    DAYNAME(created_at) as day_name,
                    DAYOFWEEK(created_at) as day_num,
                    COUNT(*) as leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted,
                    ROUND((SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2) as conversion_rate
                  FROM $leadsTable 
                  WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ?
                  GROUP BY DAYOFWEEK(created_at)
                  ORDER BY day_num ASC";
    
$weekday_stmt = mysqli_prepare($conn, $weekday_query);
mysqli_stmt_bind_param($weekday_stmt, "iss", $partner_id, $from_date, $to_date);
mysqli_stmt_execute($weekday_stmt);
$weekday_result = mysqli_stmt_get_result($weekday_stmt);
$weekday_performance = mysqli_fetch_all($weekday_result, MYSQLI_ASSOC);
mysqli_stmt_close($weekday_stmt);

// ========== 7. SUMMARY STATISTICS ==========
$summary_query = "SELECT 
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as total_converted,
                    SUM(CASE WHEN $statusCol = 'lost' THEN 1 ELSE 0 END) as total_lost,
                    SUM(COALESCE($commissionCol, 0)) as total_commission,
                    ROUND((SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2) as overall_conversion_rate,
                    AVG(CASE WHEN $statusCol = 'converted' THEN COALESCE($commissionCol, 0) ELSE NULL END) as avg_commission
                  FROM $leadsTable 
                  WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ?";
    
$summary_stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($summary_stmt, "iss", $partner_id, $from_date, $to_date);
mysqli_stmt_execute($summary_stmt);
$summary_result = mysqli_stmt_get_result($summary_stmt);
$summary_stats = mysqli_fetch_assoc($summary_result);
mysqli_stmt_close($summary_stmt);

// ========== 8. BEST PERFORMING DAY ==========
$best_day_query = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as leads,
                        SUM(CASE WHEN $statusCol = 'converted' THEN 1 ELSE 0 END) as converted
                      FROM $leadsTable 
                      WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ?
                      GROUP BY DATE(created_at)
                      ORDER BY converted DESC, leads DESC
                      LIMIT 1";
    
$best_day_stmt = mysqli_prepare($conn, $best_day_query);
mysqli_stmt_bind_param($best_day_stmt, "iss", $partner_id, $from_date, $to_date);
mysqli_stmt_execute($best_day_stmt);
$best_day_result = mysqli_stmt_get_result($best_day_stmt);
$best_day = mysqli_fetch_assoc($best_day_result);
mysqli_stmt_close($best_day_stmt);

// ========== 9. CONVERSION FUNNEL ==========
$funnel_query = "SELECT 
                    $statusCol as stage,
                    COUNT(*) as count,
                    ROUND((COUNT(*) / (SELECT COUNT(*) FROM $leadsTable WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ?)) * 100, 2) as percentage
                  FROM $leadsTable 
                  WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ?
                  GROUP BY $statusCol
                  ORDER BY FIELD($statusCol, 'new', 'contacted', 'converted', 'lost')";
    
$funnel_stmt = mysqli_prepare($conn, $funnel_query);
mysqli_stmt_bind_param($funnel_stmt, "ississ", $partner_id, $from_date, $to_date, $partner_id, $from_date, $to_date);
mysqli_stmt_execute($funnel_stmt);
$funnel_result = mysqli_stmt_get_result($funnel_stmt);
$conversion_funnel = mysqli_fetch_all($funnel_result, MYSQLI_ASSOC);
mysqli_stmt_close($funnel_stmt);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'period' => [
        'from' => $from_date,
        'to' => $to_date,
        'type' => $period,
        'days' => ceil((strtotime($to_date) - strtotime($from_date)) / (60 * 60 * 24)) + 1
    ],
    'partner' => [
        'id' => $partner_id,
        'name' => $role_data['name']
    ],
    'summary' => [
        'total_leads' => (int)($summary_stats['total_leads'] ?? 0),
        'total_converted' => (int)($summary_stats['total_converted'] ?? 0),
        'total_lost' => (int)($summary_stats['total_lost'] ?? 0),
        'total_commission' => round((float)($summary_stats['total_commission'] ?? 0), 2),
        'overall_conversion_rate' => (float)($summary_stats['overall_conversion_rate'] ?? 0),
        'average_commission' => round((float)($summary_stats['avg_commission'] ?? 0), 2)
    ],
    'best_day' => $best_day ? [
        'date' => $best_day['date'],
        'leads' => (int)$best_day['leads'],
        'converted' => (int)$best_day['converted']
    ] : null,
    'analytics' => [
        'source_performance' => $source_performance,
        'service_performance' => $service_performance,
        'daily_performance' => $daily_performance,
        'hourly_performance' => $hourly_performance,
        'growth_metrics' => $growth_data,
        'weekday_performance' => $weekday_performance,
        'conversion_funnel' => $conversion_funnel
    ]
]);

mysqli_close($conn);
?>