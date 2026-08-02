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

$query = "SELECT u.id, u.name, u.role as department,
          (SELECT COUNT(*) FROM operation_cases WHERE assigned_to = u.id AND status='completed') as cases_closed,
          (SELECT AVG(TIMESTAMPDIFF(DAY, created_at, completed_at)) FROM operation_cases WHERE assigned_to = u.id AND status='completed') as avg_resolution_days,
          (SELECT COUNT(*) FROM operation_cases WHERE assigned_to = u.id AND completed_at <= sla_due) as sla_met,
          (SELECT COUNT(*) FROM operation_cases WHERE assigned_to = u.id AND sla_due IS NOT NULL) as total_sla
          FROM users u
          WHERE u.role IN ('credit_analyst', 'dispute_team', 'support_team', 'operations_team', 'employee')
          ORDER BY cases_closed DESC";
$result = mysqli_query($conn, $query);
$team = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sla_percent = $row['total_sla'] > 0 ? round(($row['sla_met'] / $row['total_sla']) * 100) : 100;
    $rating = $sla_percent >= 95 ? 5 : ($sla_percent >= 85 ? 4 : ($sla_percent >= 75 ? 3 : 2));
    $incentive = $row['cases_closed'] * 500;
    $team[] = [
        'name' => $row['name'], 'department' => ucfirst(str_replace('_', ' ', $row['department'])),
        'cases_closed' => (int)$row['cases_closed'], 'avg_resolution_days' => round($row['avg_resolution_days'] ?? 0, 1),
        'sla_met' => $sla_percent, 'rating' => $rating, 'incentive' => $incentive
    ];
}

echo json_encode(['success' => true, 'team' => $team]);
mysqli_close($conn);
?>