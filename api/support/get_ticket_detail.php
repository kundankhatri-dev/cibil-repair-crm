<?php
session_start();
header('Content-Type: application/json');

$allowed_roles = ['support_team', 'admin', 'manager', 'partner'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
$user_role = $_SESSION['user_role'];
$user_email = $_SESSION['user_email'] ?? '';

if (!$ticket_id) {
    echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
    exit;
}

if ($user_role === 'partner') {
    // Partner can only see their own tickets
    $query = "SELECT t.* FROM support_tickets t WHERE t.id = $ticket_id AND t.client_email = '$user_email'";
} else {
    $query = "SELECT t.* FROM support_tickets t WHERE t.id = $ticket_id";
}

$result = mysqli_query($conn, $query);
$ticket = mysqli_fetch_assoc($result);

if ($ticket) {
    echo json_encode(['success' => true, 'ticket' => $ticket]);
} else {
    echo json_encode(['success' => false, 'error' => 'Ticket not found or unauthorized']);
}

mysqli_close($conn);
?>