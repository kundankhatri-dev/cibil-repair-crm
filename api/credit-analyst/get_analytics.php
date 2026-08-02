<?php
// api/credit-analyst/get_analytics.php - Analytics data
session_start();
header('Content-Type: application/json');

$allowed_roles = ['credit_analyst', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Analyst performance
$analyst_query = "SELECT a.name, COUNT(ca.id) as count 
                  FROM credit_analysis ca
                  JOIN users a ON ca.analyst_id = a.id
                  WHERE ca.status = 'analyzed'
                  GROUP BY ca.analyst_id
                  ORDER BY count DESC LIMIT 5";
$analyst_result = mysqli_query($conn, $analyst_query);
$analyst_labels = [];
$analyst_values = [];
while ($row = mysqli_fetch_assoc($analyst_result)) {
    $analyst_labels[] = $row['name'];
    $analyst_values[] = (int)$row['count'];
}

// Monthly trends (last 6 months)
$trend_labels = [];
$trend_values = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $trend_labels[] = $month;
    $start_date = date('Y-m-01', strtotime("-$i months"));
    $end_date = date('Y-m-t', strtotime("-$i months"));
    $count_query = "SELECT COUNT(*) as count FROM credit_analysis WHERE analyzed_at BETWEEN '$start_date' AND '$end_date'";
    $count_result = mysqli_query($conn, $count_query);
    $count_data = mysqli_fetch_assoc($count_result);
    $trend_values[] = (int)($count_data['count'] ?? 0);
}

echo json_encode([
    'success' => true,
    'analyst_performance' => [
        'labels' => $analyst_labels,
        'values' => $analyst_values
    ],
    'monthly_trends' => [
        'labels' => $trend_labels,
        'values' => $trend_values
    ]
]);

mysqli_close($conn);
?>