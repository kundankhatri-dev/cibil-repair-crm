<?php
header('Content-Type: application/json');

// Database connection
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Simple queries that definitely work
$users_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$total_users = $users_result ? mysqli_fetch_assoc($users_result)['count'] : 0;

$leads_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM partner_leads");
$total_leads = $leads_result ? mysqli_fetch_assoc($leads_result)['count'] : 0;

$revenue_result = mysqli_query($conn, "SELECT COALESCE(SUM(commission_amount),0) as total FROM partner_leads WHERE status = 'converted'");
$total_revenue = $revenue_result ? mysqli_fetch_assoc($revenue_result)['total'] : 0;

// Monthly trends
$monthly_result = mysqli_query($conn, "SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as leads FROM partner_leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY created_at ASC");
$monthly_trends = $monthly_result ? mysqli_fetch_all($monthly_result, MYSQLI_ASSOC) : [];

// If no monthly data, provide default
if (empty($monthly_trends)) {
    $monthly_trends = [
        ['month' => 'Jan 2025', 'leads' => 0],
        ['month' => 'Feb 2025', 'leads' => 0],
        ['month' => 'Mar 2025', 'leads' => 0],
        ['month' => 'Apr 2025', 'leads' => 0],
        ['month' => 'May 2025', 'leads' => 0],
        ['month' => 'Jun 2025', 'leads' => 0]
    ];
}

// Revenue by service
$service_result = mysqli_query($conn, "SELECT COALESCE(service_type, 'Other') as service, SUM(commission_amount) as total FROM partner_leads WHERE status = 'converted' AND service_type IS NOT NULL GROUP BY service_type");
$revenue_by_service = $service_result ? mysqli_fetch_all($service_result, MYSQLI_ASSOC) : [];

if (empty($revenue_by_service)) {
    $revenue_by_service = [
        ['service' => 'Written Off', 'total' => 0],
        ['service' => 'Settled', 'total' => 0],
        ['service' => 'Profile Correction', 'total' => 0]
    ];
}

// Return response
echo json_encode([
    'success' => true,
    'analytics' => [
        'total_stats' => [
            'total_users' => (int)$total_users,
            'total_leads' => (int)$total_leads,
            'total_revenue' => (float)$total_revenue
        ],
        'conversion_rate' => 0,
        'monthly_trends' => $monthly_trends,
        'revenue_by_service' => $revenue_by_service,
        'top_partners' => []
    ]
]);

mysqli_close($conn);
?>