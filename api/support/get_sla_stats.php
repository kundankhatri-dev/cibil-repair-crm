<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['support_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$total_sla = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM support_tickets WHERE sla_due IS NOT NULL"))['c'] ?? 1;
$sla_met = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM support_tickets WHERE resolved_at <= sla_due"))['c'] ?? 0;
$sla_breached = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM support_tickets WHERE status NOT IN ('resolved','closed') AND NOW() > sla_due"))['c'] ?? 0;
$sla_at_risk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM support_tickets WHERE status NOT IN ('resolved','closed') AND TIMESTAMPDIFF(HOUR, NOW(), sla_due) <= 24 AND TIMESTAMPDIFF(HOUR, NOW(), sla_due) > 0"))['c'] ?? 0;

$query = "SELECT t.*, u.name as client_name FROM support_tickets t 
          JOIN users u ON t.client_id = u.id 
          WHERE t.status NOT IN ('resolved','closed') AND NOW() > t.sla_due";
$result = mysqli_query($conn, $query);
$breaches = [];
while ($row = mysqli_fetch_assoc($result)) $breaches[] = $row;

echo json_encode([
    'success' => true, 'sla_met' => $total_sla > 0 ? round(($sla_met / $total_sla) * 100) : 100,
    'sla_breached' => $sla_breached, 'sla_at_risk' => $sla_at_risk, 'breaches' => $breaches
]);
mysqli_close($conn);
?>