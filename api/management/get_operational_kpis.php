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

// KPI trend (last 6 months)
$kpi_labels = []; $kpi_values = [];
for ($i = 5; $i >= 0; $i--) {
    $kpi_labels[] = date('M', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $resolved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE status='completed' AND completed_at BETWEEN '$start' AND '$end'"))['c'] ?? 0;
    $total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE created_at BETWEEN '$start' AND '$end'"))['c'] ?? 1;
    $kpi_values[] = round(($resolved / $total) * 100);
}

// Department KPIs
$depts = [
    ['name' => 'Credit Analyst', 'role' => 'credit_analyst'],
    ['name' => 'Dispute Team', 'role' => 'dispute_team'],
    ['name' => 'Support', 'role' => 'support_team'],
    ['name' => 'Operations', 'role' => 'operations_team']
];
$departments = [];
foreach ($depts as $dept) {
    $completed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='{$dept['role']}') AND status='completed'"))['c'] ?? 0;
    $avg_days = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(TIMESTAMPDIFF(DAY, created_at, completed_at)) as avg FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='{$dept['role']}') AND status='completed'"))['avg'] ?? 0;
    $sla_met = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='{$dept['role']}') AND completed_at <= sla_due"))['c'] ?? 0;
    $total_sla = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='{$dept['role']}') AND sla_due IS NOT NULL"))['c'] ?? 1;
    $departments[] = [
        'name' => $dept['name'], 'cases_completed' => $completed,
        'avg_resolution_days' => round($avg_days, 1),
        'sla_compliance' => round(($sla_met / $total_sla) * 100),
        'productivity_score' => rand(70, 98)
    ];
}

echo json_encode(['success' => true, 'kpi_data' => ['labels' => $kpi_labels, 'values' => $kpi_values], 'departments' => $departments]);
mysqli_close($conn);
?>