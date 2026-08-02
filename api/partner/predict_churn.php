<?php
// api/partner/predict_churn.php
// Predict which customers are likely to churn

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

// Get all converted customers with their activity
$query = "SELECT 
    l.id, l.customer_name, l.customer_phone, l.service_type, l.created_at as conversion_date,
    DATEDIFF(NOW(), l.created_at) as days_since_conversion,
    (SELECT COUNT(*) FROM partner_lead_followups WHERE lead_id = l.id) as followup_count,
    (SELECT COUNT(*) FROM partner_tickets WHERE partner_id = ? AND subject LIKE CONCAT('%', l.customer_name, '%')) as ticket_count
    FROM $leadsTable l
    WHERE l.partner_id = ? AND l.status = 'converted'";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $partner_id, $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$customers = mysqli_fetch_all($result, MYSQLI_ASSOC);

$churn_predictions = [];
$at_risk_count = 0;

foreach ($customers as $customer) {
    // Calculate churn risk score (0-100)
    $risk_score = 0;
    
    // Inactivity risk (older than 60 days with no follow-ups)
    if ($customer['days_since_conversion'] > 60 && $customer['followup_count'] == 0) {
        $risk_score += 40;
    } elseif ($customer['days_since_conversion'] > 30 && $customer['followup_count'] == 0) {
        $risk_score += 20;
    }
    
    // Ticket complaints risk
    if ($customer['ticket_count'] > 0) {
        $risk_score += min(30, $customer['ticket_count'] * 15);
    }
    
    // Service type based risk
    $high_risk_services = ['Credit Report Analysis', 'Profile Correction'];
    if (in_array($customer['service_type'], $high_risk_services)) {
        $risk_score += 15;
    }
    
    $risk_score = min(100, $risk_score);
    
    if ($risk_score >= 50) {
        $at_risk_count++;
    }
    
    $churn_predictions[] = [
        'customer_id' => $customer['id'],
        'customer_name' => $customer['customer_name'],
        'phone' => $customer['customer_phone'],
        'service' => $customer['service_type'],
        'days_since_conversion' => $customer['days_since_conversion'],
        'churn_risk_score' => $risk_score,
        'churn_risk_level' => $risk_score >= 70 ? 'High' : ($risk_score >= 40 ? 'Medium' : 'Low'),
        'risk_badge' => $risk_score >= 70 ? 'danger' : ($risk_score >= 40 ? 'warning' : 'success'),
        'recommended_action' => $risk_score >= 70 ? 'Call immediately - Retention offer needed' : ($risk_score >= 40 ? 'Send engagement email' : 'Monitor normally'),
        'followup_count' => $customer['followup_count']
    ];
}

// Sort by risk score (highest first)
usort($churn_predictions, function($a, $b) {
    return $b['churn_risk_score'] - $a['churn_risk_score'];
});

echo json_encode([
    'success' => true,
    'predictions' => $churn_predictions,
    'summary' => [
        'total_customers' => count($customers),
        'high_risk_count' => count(array_filter($churn_predictions, fn($c) => $c['churn_risk_score'] >= 70)),
        'medium_risk_count' => count(array_filter($churn_predictions, fn($c) => $c['churn_risk_score'] >= 40 && $c['churn_risk_score'] < 70)),
        'low_risk_count' => count(array_filter($churn_predictions, fn($c) => $c['churn_risk_score'] < 40)),
        'at_risk_percentage' => round(($at_risk_count / max(count($customers), 1)) * 100, 1)
    ],
    'retention_suggestions' => [
        'Send exclusive offers to high-risk customers',
        'Schedule personal follow-up calls',
        'Share success stories and testimonials',
        'Offer loyalty discounts for continued engagement'
    ]
]);

mysqli_close($conn);
?>