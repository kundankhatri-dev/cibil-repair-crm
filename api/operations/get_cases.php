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

$status = isset($_GET['status']) ? $_GET['status'] : '';
$dept = isset($_GET['dept']) ? $_GET['dept'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT c.*, u.name as client_name, a.name as assigned_to_name,
          CASE WHEN NOW() > c.sla_due AND c.status NOT IN ('completed','closed') THEN 'Breached'
               WHEN TIMESTAMPDIFF(HOUR, NOW(), c.sla_due) <= 24 AND c.status NOT IN ('completed','closed') THEN 'At Risk'
               ELSE 'On Track' END as sla_status,
          CASE WHEN NOW() > c.sla_due AND c.status NOT IN ('completed','closed') THEN 'sla-critical'
               WHEN TIMESTAMPDIFF(HOUR, NOW(), c.sla_due) <= 24 AND c.status NOT IN ('completed','closed') THEN 'sla-warning'
               ELSE 'sla-good' END as sla_class
          FROM operation_cases c
          JOIN users u ON c.client_id = u.id
          LEFT JOIN users a ON c.assigned_to = a.id";
$where = [];
if ($status) $where[] = "c.status = '$status'";
if ($dept) $where[] = "a.role = '$dept'";
if ($search) $where[] = "(c.case_no LIKE '%$search%' OR u.name LIKE '%$search%')";
if ($where) $query .= " WHERE " . implode(' AND ', $where);
$query .= " ORDER BY c.created_at DESC";

$result = mysqli_query($conn, $query);
$cases = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['assigned_to'] = $row['assigned_to_name'];
    $cases[] = $row;
}
echo json_encode(['success' => true, 'cases' => $cases]);
mysqli_close($conn);
?>