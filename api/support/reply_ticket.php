<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['support_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ticket_id = $input['ticket_id'] ?? 0;
$message = $input['message'] ?? '';
$user_id = $_SESSION['user_id'];

if (!$ticket_id || !$message) {
    echo json_encode(['success' => false, 'error' => 'Ticket ID and message required']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$query = "INSERT INTO ticket_replies (ticket_id, user_id, message, is_admin) VALUES ($ticket_id, $user_id, '$message', 1)";
mysqli_query($conn, $query);
mysqli_query($conn, "UPDATE support_tickets SET status = 'in_progress' WHERE id = $ticket_id");
echo json_encode(['success' => true, 'message' => 'Reply sent']);
mysqli_close($conn);
?>