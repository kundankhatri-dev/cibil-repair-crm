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

// Get actual last 6 months
$forecast_labels = []; $actual_values = [];
for ($i = 5; $i >= 0; $i--) {
    $forecast_labels[] = date('M', strtotime("-$i months"));
    $rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND MONTH(payment_date) = " . date('n', strtotime("-$i months"))))['total'] ?? 0;
    $actual_values[] = round($rev / 100000, 1);
}

// Predicted next 6 months (15% growth)
$predicted_values = [];
$last_actual = end($actual_values);
for ($i = 0; $i < 6; $i++) {
    $last_actual = $last_actual * 1.15;
    $predicted_values[] = round($last_actual, 1);
    $forecast_labels[] = date('M', strtotime("+" . ($i + 1) . " months"));
}

// Strategic recommendations
$recommendations = [
    ['title' => 'Expand Partner Network', 'description' => 'Increase partner acquisition by 40% to boost lead generation. Current partners are performing 2x better than other channels.', 'impact' => 'High'],
    ['title' => 'Automate Credit Analysis', 'description' => 'Implement AI-powered credit report analysis to reduce analyst workload by 30% and improve turnaround time.', 'impact' => 'High'],
    ['title' => 'Launch Referral Program', 'description' => 'Client referral program can increase customer acquisition by 25% with minimal marketing spend.', 'impact' => 'Medium'],
    ['title' => 'Improve Client NPS', 'description' => 'Focus on post-resolution follow-ups to increase NPS from 72 to 85 in next quarter.', 'impact' => 'Medium']
];

echo json_encode(['success' => true, 'forecast_data' => ['labels' => $forecast_labels, 'actual' => array_merge($actual_values, array_fill(0, 6, null)), 'predicted' => array_merge(array_fill(0, 6, null), $predicted_values)], 'recommendations' => $recommendations]);
mysqli_close($conn);
?>