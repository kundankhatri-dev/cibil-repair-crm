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

$query = "SELECT i.*, u.name as client_name FROM invoices i JOIN users u ON i.client_id = u.id ORDER BY i.created_at DESC";
$result = mysqli_query($conn, $query);
$invoices = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['date'] = date('d M Y', strtotime($row['issue_date']));
    $invoices[] = $row;
}
echo json_encode(['success' => true, 'invoices' => $invoices]);
mysqli_close($conn);
?>