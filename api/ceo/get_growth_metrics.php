<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['ceo', 'founder', 'admin', 'director'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

// Monthly growth rates (last 6 months)
$growth_labels = []; $growth_values = [];
for ($i = 5; $i >= 0; $i--) {
    $growth_labels[] = date('M', strtotime("-$i months"));
    $this_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND MONTH(payment_date) = " . date('n', strtotime("-$i months")) . " AND YEAR(payment_date) = " . date('Y', strtotime("-$i months"))))['total'] ?? 0;
    $last_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND MONTH(payment_date) = " . date('n', strtotime("-" . ($i+1) . " months")) . " AND YEAR(payment_date) = " . date('Y', strtotime("-" . ($i+1) . " months"))))['total'] ?? 1;
    $growth_values[] = round((($this_month - $last_month) / $last_month) * 100);
}

// Growth drivers
$drivers = [
    ['driver' => 'Website Traffic', 'value' => '25,430', 'growth' => 18, 'target' => '30,000', 'status' => 'on_track'],
    ['driver' => 'Partner Referrals', 'value' => '342', 'growth' => 25, 'target' => '400', 'status' => 'on_track'],
    ['driver' => 'Conversion Rate', 'value' => '24%', 'growth' => 5, 'target' => '30%', 'status' => 'attention'],
    ['driver' => 'Avg Order Value', 'value' => '₹8,450', 'growth' => 12, 'target' => '₹10,000', 'status' => 'on_track'],
    ['driver' => 'Customer LTV', 'value' => '₹35,000', 'growth' => 15, 'target' => '₹50,000', 'status' => 'attention']
];

echo json_encode(['success' => true, 'growth_data' => ['labels' => $growth_labels, 'values' => $growth_values], 'drivers' => $drivers]);
mysqli_close($conn);
?>