<?php
header('Content-Type: application/json');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Connection failed']);
    exit;
}

$data = [];

// Total users
$users_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$data['total_users'] = $users_result ? (int)mysqli_fetch_assoc($users_result)['count'] : 0;

// Total leads
$leads_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM partner_leads");
$data['total_leads'] = $leads_result ? (int)mysqli_fetch_assoc($leads_result)['count'] : 0;

// Total revenue
$revenue_result = mysqli_query($conn, "SELECT COALESCE(SUM(commission_amount),0) as total FROM partner_leads WHERE status = 'converted'");
$data['total_revenue'] = $revenue_result ? (float)mysqli_fetch_assoc($revenue_result)['total'] : 0;

// Conversion rate
$conv_result = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted FROM partner_leads");
if ($conv_result) {
    $conv_data = mysqli_fetch_assoc($conv_result);
    $data['conversion_rate'] = ($conv_data['total'] > 0) ? round(($conv_data['converted'] / $conv_data['total']) * 100, 2) : 0;
} else {
    $data['conversion_rate'] = 0;
}

// Monthly trends - simplified
$monthly_data = [];
$monthly_result = mysqli_query($conn, "SELECT DATE_FORMAT(created_at, '%b %Y') as month, COUNT(*) as leads FROM partner_leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY created_at ASC");
if ($monthly_result) {
    while ($row = mysqli_fetch_assoc($monthly_result)) {
        $monthly_data[] = $row;
    }
}
$data['monthly_trends'] = $monthly_data;

// Revenue by service - simplified
$service_data = [];
$service_result = mysqli_query($conn, "SELECT service_type as service, SUM(commission_amount) as total FROM partner_leads WHERE status = 'converted' AND service_type IS NOT NULL GROUP BY service_type");
if ($service_result) {
    while ($row = mysqli_fetch_assoc($service_result)) {
        $service_data[] = $row;
    }
}
$data['revenue_by_service'] = $service_data;

echo json_encode(['success' => true, 'analytics' => $data]);

mysqli_close($conn);
?>