<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['finance_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM employee_incentives"))['total'] ?? 0;
$paid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM employee_incentives WHERE status='paid'"))['total'] ?? 0;
$pending = $total - $paid;

$query = "SELECT ei.*, u.name as employee_name FROM employee_incentives ei JOIN users u ON ei.employee_id = u.id ORDER BY ei.created_at DESC";
$result = mysqli_query($conn, $query);
$incentives = [];
while ($row = mysqli_fetch_assoc($result)) $incentives[] = $row;

echo json_encode(['success' => true, 'total' => (float)$total, 'paid' => (float)$paid, 'pending' => (float)$pending, 'incentives' => $incentives]);
mysqli_close($conn);
?>