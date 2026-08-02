<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

// ROI by campaign type
$query = "SELECT campaign_type, 
          SUM(budget) as total_budget, 
          SUM(actual_cost) as total_cost,
          SUM(expected_revenue) as expected_revenue,
          SUM(actual_revenue) as actual_revenue,
          SUM(leads_generated) as total_leads,
          SUM(conversions) as total_conversions
          FROM marketing_campaigns 
          GROUP BY campaign_type";
$result = mysqli_query($conn, $query);

$roi_by_type = [];
while ($row = mysqli_fetch_assoc($result)) {
    $roi = 0;
    if ($row['total_cost'] > 0) {
        $roi = (($row['actual_revenue'] - $row['total_cost']) / $row['total_cost']) * 100;
    }
    $row['roi'] = round($roi, 2);
    $roi_by_type[] = $row;
}

// Monthly performance
$query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
          SUM(actual_cost) as monthly_cost,
          SUM(actual_revenue) as monthly_revenue,
          SUM(leads_generated) as monthly_leads
          FROM marketing_campaigns
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY month
          ORDER BY month DESC";
$result = mysqli_query($conn, $query);

$monthly = [];
while ($row = mysqli_fetch_assoc($result)) {
    $monthly[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => [
        'roi_by_type' => $roi_by_type,
        'monthly_performance' => $monthly
    ]
]);
?>