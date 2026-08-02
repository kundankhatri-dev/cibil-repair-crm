<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['operations_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$dept_labels = ['Credit Analyst', 'Dispute Team', 'Support', 'Operations'];
$dept_values = [
    mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='credit_analyst') AND status='completed'"))['c'] ?? 0,
    mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='dispute_team') AND status='completed'"))['c'] ?? 0,
    mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='support_team') AND status='completed'"))['c'] ?? 0,
    mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='operations_team') AND status='completed'"))['c'] ?? 0
];

$kpi_labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
$kpi_values = [rand(70, 95), rand(72, 96), rand(75, 98), rand(78, 99)];

echo json_encode([
    'success' => true,
    'dept_performance' => ['labels' => $dept_labels, 'values' => $dept_values],
    'kpi_data' => ['labels' => $kpi_labels, 'values' => $kpi_values]
]);
mysqli_close($conn);
?>