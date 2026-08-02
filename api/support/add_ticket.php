<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['support_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$client_id = $input['client_id'] ?? 0;
$subject = $input['subject'] ?? '';
$category = $input['category'] ?? '';
$priority = $input['priority'] ?? 'medium';
$message = $input['message'] ?? '';

if (!$client_id || !$subject) {
    echo json_encode(['success' => false, 'error' => 'Client and subject required']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$ticket_no = 'TKT' . date('Ymd') . rand(1000, 9999);
$sla_due = date('Y-m-d H:i:s', strtotime('+24 hours'));
$query = "INSERT INTO support_tickets (client_id, ticket_no, subject, category, priority, message, sla_due) 
          VALUES ($client_id, '$ticket_no', '$subject', '$category', '$priority', '$message', '$sla_due')";
mysqli_query($conn, $query);
echo json_encode(['success' => true, 'message' => 'Ticket created', 'ticket_no' => $ticket_no]);
mysqli_close($conn);
?>