<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

$response = [];

try {
    // Total campaigns
    $query = "SELECT COUNT(*) as total_campaigns FROM marketing_campaigns";
    $result = mysqli_query($conn, $query);
    $total_campaigns = mysqli_fetch_assoc($result)['total_campaigns'];
    
    // Active campaigns
    $query = "SELECT COUNT(*) as active_campaigns FROM marketing_campaigns WHERE status = 'active'";
    $result = mysqli_query($conn, $query);
    $active_campaigns = mysqli_fetch_assoc($result)['active_campaigns'];
    
    // Total leads generated
    $query = "SELECT SUM(leads_generated) as total_leads FROM marketing_campaigns";
    $result = mysqli_query($conn, $query);
    $total_leads = mysqli_fetch_assoc($result)['total_leads'] ?? 0;
    
    // Total conversions
    $query = "SELECT SUM(conversions) as total_conversions FROM marketing_campaigns";
    $result = mysqli_query($conn, $query);
    $total_conversions = mysqli_fetch_assoc($result)['total_conversions'] ?? 0;
    
    // ROI (Return on Investment)
    $query = "SELECT SUM(actual_revenue) as total_revenue, SUM(actual_cost) as total_cost FROM marketing_campaigns";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    $total_revenue = $data['total_revenue'] ?? 0;
    $total_cost = $data['total_cost'] ?? 0;
    
    $roi = 0;
    if ($total_cost > 0) {
        $roi = (($total_revenue - $total_cost) / $total_cost) * 100;
    }
    
    // Average conversion rate
    $query = "SELECT AVG(conversion_rate) as avg_conversion FROM lead_sources WHERE is_active = 1";
    $result = mysqli_query($conn, $query);
    $avg_conversion = mysqli_fetch_assoc($result)['avg_conversion'] ?? 0;
    
    $response = [
        'success' => true,
        'data' => [
            'total_campaigns' => (int)$total_campaigns,
            'active_campaigns' => (int)$active_campaigns,
            'total_leads' => (int)$total_leads,
            'total_conversions' => (int)$total_conversions,
            'total_revenue' => (float)$total_revenue,
            'total_cost' => (float)$total_cost,
            'roi' => round($roi, 2),
            'avg_conversion_rate' => round($avg_conversion, 2)
        ]
    ];
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
?>