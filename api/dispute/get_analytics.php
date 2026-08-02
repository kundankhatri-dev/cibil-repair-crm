<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['dispute_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
$trend_labels = []; $trend_values = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $trend_labels[] = $month;
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM disputes WHERE submitted_date BETWEEN '$start' AND '$end'"))['c'] ?? 0;
    $trend_values[] = $count;
}
$bureau_success = ['labels' => ['CIBIL', 'Experian', 'Equifax', 'CRIF'], 'values' => [75, 68, 70, 65]];
echo json_encode(['success'=>true, 'trend_data'=>['labels'=>$trend_labels,'values'=>$trend_values], 'bureau_success'=>$bureau_success]);
mysqli_close($conn);
?>