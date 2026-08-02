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

$query = "SELECT u.id, u.name, u.role as department,
          (SELECT COUNT(*) FROM operation_cases WHERE assigned_to = u.id AND status NOT IN ('completed','closed')) as assigned_cases,
          (SELECT COUNT(*) FROM operation_cases WHERE assigned_to = u.id AND status = 'completed' AND MONTH(completed_at) = MONTH(CURDATE())) as completed_month,
          (SELECT COUNT(*) FROM operation_cases WHERE assigned_to = u.id AND status = 'pending') as pending_cases,
          u.status
          FROM users u
          WHERE u.role IN ('credit_analyst', 'dispute_team', 'support_team', 'operations_team')
          ORDER BY assigned_cases DESC";
$result = mysqli_query($conn, $query);
$workload = [];
while ($row = mysqli_fetch_assoc($result)) {
    $max_load = 15;
    $row['workload_percent'] = min(100, round(($row['assigned_cases'] / $max_load) * 100));
    $workload[] = $row;
}
echo json_encode(['success' => true, 'workload' => $workload]);
mysqli_close($conn);
?>