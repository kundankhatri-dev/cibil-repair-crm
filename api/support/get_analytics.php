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

$query = "SELECT a.name, COUNT(t.id) as count FROM support_tickets t 
          JOIN users a ON t.assigned_to = a.id 
          WHERE t.status = 'resolved' GROUP BY t.assigned_to ORDER BY count DESC LIMIT 5";
$result = mysqli_query($conn, $query);
$agents = []; $values = [];
while ($row = mysqli_fetch_assoc($result)) {
    $agents[] = $row['name'];
    $values[] = $row['count'];
}

$cats = ['Score Query', 'Dispute Status', 'Loan Status', 'Refund Query', 'Document Request'];
$res_times = [];
foreach ($cats as $cat) {
    $avg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg FROM support_tickets WHERE category='$cat' AND status='resolved'"))['avg'] ?? 0;
    $res_times[] = round($avg);
}

echo json_encode([
    'success' => true,
    'agent_performance' => ['labels' => $agents, 'values' => $values],
    'resolution_by_category' => ['labels' => $cats, 'values' => $res_times]
]);
mysqli_close($conn);
?>