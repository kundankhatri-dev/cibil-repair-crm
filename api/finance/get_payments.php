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

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$from = isset($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) ? $_GET['to'] : '';

$query = "SELECT p.*, u.name as client_name FROM payments p JOIN users u ON p.client_id = u.id WHERE 1=1";
if ($search) $query .= " AND (u.name LIKE '%$search%' OR p.transaction_id LIKE '%$search%')";
if ($status) $query .= " AND p.status = '$status'";
if ($from) $query .= " AND p.payment_date >= '$from'";
if ($to) $query .= " AND p.payment_date <= '$to'";
$query .= " ORDER BY p.created_at DESC";

$result = mysqli_query($conn, $query);
$payments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['date'] = date('d M Y', strtotime($row['payment_date'] ?? $row['created_at']));
    $payments[] = $row;
}
echo json_encode(['success' => true, 'payments' => $payments]);
mysqli_close($conn);
?>