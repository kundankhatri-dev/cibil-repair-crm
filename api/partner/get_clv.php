<?php
// api/partner/get_clv.php
// Calculate Customer Lifetime Value

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

// Get all converted customers with their commission
$query = "SELECT 
    customer_name,
    commission_amount,
    created_at,
    service_type
    FROM $leadsTable 
    WHERE partner_id = ? AND status = 'converted'";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$customers = mysqli_fetch_all($result, MYSQLI_ASSOC);

$total_commission = array_sum(array_column($customers, 'commission_amount'));
$total_customers = count($customers);

// Calculate average commission per customer
$avg_commission = $total_customers > 0 ? $total_commission / $total_customers : 0;

// Calculate retention rate (assuming 12 months)
$current_year = date('Y');
$customers_12_months_ago = 0;
$customers_current = 0;

foreach ($customers as $customer) {
    $year = date('Y', strtotime($customer['created_at']));
    if ($year == $current_year - 1) {
        $customers_12_months_ago++;
    }
    if ($year == $current_year) {
        $customers_current++;
    }
}

$retention_rate = $customers_12_months_ago > 0 ? ($customers_current / $customers_12_months_ago) : 0.7; // Default 70%
$customer_lifetime = 1 / (1 - min(0.95, max(0.5, $retention_rate)));

// CLV = Average Commission × Customer Lifetime
$clv = $avg_commission * $customer_lifetime;

// Segment customers by value
$segments = [
    'high_value' => 0,
    'medium_value' => 0,
    'low_value' => 0
];

foreach ($customers as $customer) {
    if ($customer['commission_amount'] >= 5000) {
        $segments['high_value']++;
    } elseif ($customer['commission_amount'] >= 2000) {
        $segments['medium_value']++;
    } else {
        $segments['low_value']++;
    }
}

echo json_encode([
    'success' => true,
    'clv_metrics' => [
        'average_customer_lifetime_value' => round($clv, 2),
        'clv_formatted' => '₹' . number_format($clv, 2),
        'average_commission_per_customer' => round($avg_commission, 2),
        'avg_commission_formatted' => '₹' . number_format($avg_commission, 2),
        'customer_retention_rate' => round($retention_rate * 100, 1) . '%',
        'estimated_customer_lifetime_months' => round($customer_lifetime, 1),
        'total_customers' => $total_customers,
        'total_revenue' => round($total_commission, 2),
        'total_revenue_formatted' => '₹' . number_format($total_commission, 2)
    ],
    'customer_segments' => $segments,
    'service_breakdown' => array_count_values(array_column($customers, 'service_type')),
    'recommendations' => [
        'Focus on high-value customers for retention',
        'Upsell medium-value customers to increase CLV',
        'Implement loyalty program for repeat business'
    ]
]);

mysqli_close($conn);
?>