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
$query = "SELECT e.*, t.ticket_no, u.name as client_name, a.name as escalated_to_name 
          FROM ticket_escalations e 
          JOIN support_tickets t ON e.ticket_id = t.id 
          JOIN users u ON t.client_id = u.id 
          LEFT JOIN users a ON e.escalated_to = a.id 
          ORDER BY e.escalated_at DESC";
$result = mysqli_query($conn, $query);
$escalations = [];
while ($row = mysqli_fetch_assoc($result)) $escalations[] = $row;
echo json_encode(['success' => true, 'escalations' => $escalations]);
mysqli_close($conn);
?>