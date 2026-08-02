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
$query = "SELECT c.*, u.name as customer_name, a.name as agent_name 
          FROM call_logs c 
          LEFT JOIN users u ON c.client_id = u.id 
          LEFT JOIN users a ON c.agent_id = a.id 
          ORDER BY c.call_time DESC LIMIT 20";
$result = mysqli_query($conn, $query);
$calls = [];
while ($row = mysqli_fetch_assoc($result)) $calls[] = $row;
echo json_encode(['success' => true, 'calls' => $calls]);
mysqli_close($conn);
?>