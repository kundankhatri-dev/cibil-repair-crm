<?php
// api/partner/forecast_revenue.php
// AI-powered revenue forecasting

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];

$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// Get last 6 months commission data
$query = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    SUM(commission_amount) as total_commission,
    COUNT(*) as conversions
    FROM $leadsTable 
    WHERE partner_id = ? AND status = 'converted' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$historical = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Simple linear regression for forecasting
$forecast = [];
if (count($historical) >= 3) {
    $months = range(1, count($historical));
    $commissions = array_column($historical, 'total_commission');
    
    $n = count($months);
    $sum_x = array_sum($months);
    $sum_y = array_sum($commissions);
    $sum_xy = array_sum(array_map(function($x, $y) { return $x * $y; }, $months, $commissions));
    $sum_x2 = array_sum(array_map(function($x) { return $x * $x; }, $months));
    
    $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);
    $intercept = ($sum_y - $slope * $sum_x) / $n;
    
    // Forecast next 3 months
    for ($i = 1; $i <= 3; $i++) {
        $next_month = $n + $i;
        $predicted = round($intercept + $slope * $next_month, 2);
        $forecast[] = [
            'month' => date('M Y', strtotime("+$i months")),
            'predicted_commission' => $predicted,
            'predicted_commission_formatted' => '₹' . number_format($predicted, 2),
            'confidence_low' => round($predicted * 0.8, 2),
            'confidence_high' => round($predicted * 1.2, 2)
        ];
    }
}

// Get current month performance vs target
$current_month = date('Y-m');
$current_query = "SELECT SUM(commission_amount) as current_commission, COUNT(*) as current_conversions 
                  FROM $leadsTable 
                  WHERE partner_id = ? AND status = 'converted' 
                  AND DATE_FORMAT(created_at, '%Y-%m') = ?";
$current_stmt = mysqli_prepare($conn, $current_query);
mysqli_stmt_bind_param($current_stmt, "is", $partner_id, $current_month);
mysqli_stmt_execute($current_stmt);
$current_result = mysqli_stmt_get_result($current_stmt);
$current = mysqli_fetch_assoc($current_result);

$monthly_target = 10000; // Configurable
$target_progress = min(100, round(($current['current_commission'] / $monthly_target) * 100, 1));

echo json_encode([
    'success' => true,
    'historical_data' => $historical,
    'forecast' => $forecast,
    'current_performance' => [
        'month' => date('F Y'),
        'current_commission' => round($current['current_commission'], 2),
        'current_commission_formatted' => '₹' . number_format($current['current_commission'], 2),
        'current_conversions' => (int)$current['current_conversions'],
        'monthly_target' => $monthly_target,
        'target_progress' => $target_progress,
        'target_progress_formatted' => $target_progress . '%',
        'days_remaining' => date('t') - date('j'),
        'projected_month_end' => round($current['current_commission'] / date('j') * date('t'), 2)
    ],
    'trend_analysis' => [
        'trend_direction' => $slope > 0 ? 'upward' : ($slope < 0 ? 'downward' : 'stable'),
        'growth_rate' => isset($slope) ? round(($slope / max($commissions[0], 1)) * 100, 1) : 0
    ],
    'forecast_summary' => [
        'next_month_forecast' => $forecast[0]['predicted_commission_formatted'] ?? '₹0',
        'quarter_forecast' => isset($forecast) ? '₹' . number_format(array_sum(array_column($forecast, 'predicted_commission')), 2) : '₹0'
    ]
]);

mysqli_close($conn);
?>