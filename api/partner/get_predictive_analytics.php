<?php
// api/partner/get_predictive_analytics.php
// AI-powered lead conversion prediction

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Get lead data for analysis
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// Fetch all leads with their attributes
$query = "SELECT id, customer_name, customer_phone, service_type, source, 
          status, created_at, DATEDIFF(NOW(), created_at) as days_old,
          CASE 
              WHEN source = 'Referral' THEN 25
              WHEN source = 'Website' THEN 20
              WHEN source = 'Social Media' THEN 15
              WHEN source = 'Call' THEN 10
              ELSE 5
          END as source_score,
          CASE 
              WHEN service_type IN ('Written Off Clearance', 'Suit Filed Clearance') THEN 30
              WHEN service_type IN ('Settled Clearance', 'Credit Report Analysis') THEN 20
              ELSE 10
          END as service_score,
          CASE 
              WHEN days_old <= 2 THEN 40
              WHEN days_old <= 7 THEN 30
              WHEN days_old <= 14 THEN 20
              WHEN days_old <= 30 THEN 10
              ELSE 5
          END as recency_score
          FROM $leadsTable 
          WHERE partner_id = ? AND status != 'converted' AND status != 'lost'";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leads = mysqli_fetch_all($result, MYSQLI_ASSOC);

$predictions = [];
foreach ($leads as $lead) {
    // Calculate conversion probability
    $probability = min(95, $lead['source_score'] + $lead['service_score'] + $lead['recency_score']);
    
    // Adjust based on status
    if ($lead['status'] === 'contacted') {
        $probability += 10;
    }
    
    // Determine recommended action
    if ($probability >= 70) {
        $action = '🟢 Contact Immediately - High conversion potential';
        $priority = 'high';
    } elseif ($probability >= 40) {
        $action = '🟡 Schedule Follow-up - Medium priority';
        $priority = 'medium';
    } else {
        $action = '🔵 Nurture - Send educational content';
        $priority = 'low';
    }
    
    $predictions[] = [
        'lead_id' => $lead['id'],
        'customer_name' => $lead['customer_name'],
        'customer_phone' => $lead['customer_phone'],
        'service_type' => $lead['service_type'],
        'current_status' => $lead['status'],
        'conversion_probability' => $probability,
        'conversion_percentage' => $probability . '%',
        'probability_level' => $probability >= 70 ? 'High' : ($probability >= 40 ? 'Medium' : 'Low'),
        'recommended_action' => $action,
        'priority' => $priority,
        'estimated_conversion_time' => $probability >= 70 ? '1-3 days' : ($probability >= 40 ? '1-2 weeks' : '3-4 weeks'),
        'score_breakdown' => [
            'source_score' => $lead['source_score'],
            'service_score' => $lead['service_score'],
            'recency_score' => $lead['recency_score']
        ]
    ];
}

// Sort by probability (highest first)
usort($predictions, function($a, $b) {
    return $b['conversion_probability'] - $a['conversion_probability'];
});

// Calculate summary statistics
$high_potential = count(array_filter($predictions, fn($p) => $p['conversion_probability'] >= 70));
$medium_potential = count(array_filter($predictions, fn($p) => $p['conversion_probability'] >= 40 && $p['conversion_probability'] < 70));
$low_potential = count(array_filter($predictions, fn($p) => $p['conversion_probability'] < 40));

echo json_encode([
    'success' => true,
    'predictions' => $predictions,
    'summary' => [
        'total_leads_analyzed' => count($predictions),
        'high_potential_count' => $high_potential,
        'medium_potential_count' => $medium_potential,
        'low_potential_count' => $low_potential,
        'average_conversion_probability' => count($predictions) > 0 ? round(array_sum(array_column($predictions, 'conversion_probability')) / count($predictions), 1) : 0,
        'potential_revenue' => count($predictions) > 0 ? round((array_sum(array_column($predictions, 'conversion_probability')) / 100) * 10000, 2) : 0
    ],
    'generated_at' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>