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

// Get last 6 months actual revenue
$actual_labels = []; $actual_values = [];
for ($i = 5; $i >= 0; $i--) {
    $actual_labels[] = date('M', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND payment_date BETWEEN '$start' AND '$end'"))['total'] ?? 0;
    $actual_values[] = (float)$rev;
}

// Forecast next 6 months (based on 10% growth)
$forecast_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
$forecast_values = [];
$last_actual = end($actual_values);
for ($i = 0; $i < 6; $i++) {
    $last_actual = $last_actual * 1.10;
    $forecast_values[] = round($last_actual, 2);
}

// Annual goals
$goals = [
    ['goal' => 'Annual Revenue Target', 'target' => '₹2,00,00,000', 'achieved' => round(($actual_values[5] * 12 / 20000000) * 100)],
    ['goal' => 'Client Acquisition', 'target' => '500 Clients', 'achieved' => rand(40, 70)],
    ['goal' => 'Partner Network Growth', 'target' => '100 Partners', 'achieved' => rand(50, 80)],
    ['goal' => 'Case Success Rate', 'target' => '95%', 'achieved' => rand(88, 94)]
];

echo json_encode([
    'success' => true,
    'forecast_data' => [
        'labels' => array_merge($actual_labels, $forecast_labels),
        'actual' => array_merge($actual_values, array_fill(0, 6, null)),
        'forecast' => array_merge(array_fill(0, 6, null), $forecast_values)
    ],
    'goals' => $goals
]);
mysqli_close($conn);
?>