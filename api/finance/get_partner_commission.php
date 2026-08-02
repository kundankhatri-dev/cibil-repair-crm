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

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission) as total FROM partner_commission"))['total'] ?? 0;
$paid = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission) as total FROM partner_commission WHERE status='paid'"))['total'] ?? 0;
$pending = $total - $paid;

$query = "SELECT pc.*, p.name as partner_name, u.name as client_name 
          FROM partner_commission pc 
          LEFT JOIN users p ON pc.partner_id = p.id 
          LEFT JOIN users u ON pc.client_id = u.id 
          ORDER BY pc.created_at DESC";
$result = mysqli_query($conn, $query);
$commissions = [];
while ($row = mysqli_fetch_assoc($result)) $commissions[] = $row;

echo json_encode(['success' => true, 'total' => (float)$total, 'paid' => (float)$paid, 'pending' => (float)$pending, 'commissions' => $commissions]);
mysqli_close($conn);
?>