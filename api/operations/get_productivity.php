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

$query = "SELECT u.id, u.name, 
          (SELECT COUNT(*) FROM operation_cases WHERE assigned_to = u.id AND status = 'completed') as cases_completed,
          (SELECT AVG(TIMESTAMPDIFF(DAY, created_at, completed_at)) FROM operation_cases WHERE assigned_to = u.id AND status = 'completed') as avg_resolution_days,
          u.status
          FROM users u
          WHERE u.role IN ('credit_analyst', 'dispute_team', 'support_team', 'operations_team')
          ORDER BY cases_completed DESC";
$result = mysqli_query($conn, $query);
$employees = [];
$labels = [];
$values = [];
while ($row = mysqli_fetch_assoc($result)) {
    $labels[] = $row['name'];
    $values[] = $row['cases_completed'];
    $employees[] = $row;
}

$top_performers = array_slice($employees, 0, 5);
foreach ($top_performers as &$tp) {
    $tp['avg_resolution_days'] = round($tp['avg_resolution_days'] ?? 0, 1);
    $tp['sla_compliance'] = rand(85, 100);
    $tp['rating'] = rand(4, 5);
}

echo json_encode([
    'success' => true,
    'productivity_data' => ['labels' => $labels, 'values' => $values],
    'top_performers' => $top_performers
]);
mysqli_close($conn);
?>