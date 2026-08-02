<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['loan_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
$labels = []; $values = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M', strtotime("-$i months"));
    $labels[] = $month;
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM loan_applications WHERE created_at BETWEEN '$start' AND '$end'"))['c'] ?? 0;
    $values[] = $cnt;
}
$loan_types = ['Home Loan', 'Personal Loan', 'Business Loan', 'Loan Against Property', 'Credit Card'];
$dist_values = [];
foreach ($loan_types as $type) {
    $dist_values[] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM loan_applications WHERE loan_type='$type'"))['c'] ?? 0;
}
echo json_encode(['success'=>true, 'monthly_data'=>['labels'=>$labels,'values'=>$values], 'distribution'=>['labels'=>$loan_types,'values'=>$dist_values]]);
mysqli_close($conn);
?>