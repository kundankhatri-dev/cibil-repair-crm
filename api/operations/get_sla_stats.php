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

$total_sla = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE sla_due IS NOT NULL"))['c'] ?? 1;
$sla_met = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE completed_at <= sla_due"))['c'] ?? 0;
$sla_at_risk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE status NOT IN ('completed','closed') AND TIMESTAMPDIFF(HOUR, NOW(), sla_due) <= 24 AND TIMESTAMPDIFF(HOUR, NOW(), sla_due) > 0"))['c'] ?? 0;
$sla_breached = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE status NOT IN ('completed','closed') AND NOW() > sla_due"))['c'] ?? 0;

$cases_at_risk = mysqli_query($conn, "SELECT c.*, u.name as client_name, a.name as assigned_to 
    FROM operation_cases c 
    JOIN users u ON c.client_id = u.id 
    LEFT JOIN users a ON c.assigned_to = a.id 
    WHERE c.status NOT IN ('completed','closed') AND (NOW() > c.sla_due OR TIMESTAMPDIFF(HOUR, NOW(), c.sla_due) <= 24)");
$risk_cases = [];
while ($row = mysqli_fetch_assoc($cases_at_risk)) {
    $row['sla_due'] = date('d M Y', strtotime($row['sla_due']));
    $risk_cases[] = $row;
}

echo json_encode([
    'success' => true, 'sla_met' => $total_sla > 0 ? round(($sla_met / $total_sla) * 100) : 100,
    'sla_at_risk' => $sla_at_risk, 'sla_breached' => $sla_breached,
    'cases_at_risk' => $risk_cases
]);
mysqli_close($conn);
?>