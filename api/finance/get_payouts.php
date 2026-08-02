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

$query = "SELECT pr.*, 
          CASE WHEN pr.recipient_type = 'partner' THEN p.name ELSE e.name END as recipient_name 
          FROM payout_requests pr
          LEFT JOIN users p ON pr.recipient_type = 'partner' AND pr.recipient_id = p.id
          LEFT JOIN users e ON pr.recipient_type = 'employee' AND pr.recipient_id = e.id
          ORDER BY pr.created_at DESC";
$result = mysqli_query($conn, $query);
$payouts = [];
while ($row = mysqli_fetch_assoc($result)) $payouts[] = $row;

echo json_encode(['success' => true, 'payouts' => $payouts]);
mysqli_close($conn);
?>