<?php
session_start();
header('Content-Type: application/json');

$allowed_roles = ['support_team', 'admin', 'manager', 'partner'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$user_email = $_SESSION['user_email'] ?? '';
$user_name = $_SESSION['user_name'] ?? 'Partner';
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');
$priority = trim($input['priority'] ?? 'medium');
$category = trim($input['category'] ?? 'general');

if (empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Subject and message are required']);
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

// Generate unique ticket number
$ticket_no = 'TKT' . date('Ymd') . rand(1000, 9999);

// Calculate SLA due date (48 hours from now for high priority, 72 for medium, 120 for low)
$sla_hours = $priority === 'high' ? 48 : ($priority === 'medium' ? 72 : 120);
$sla_due = date('Y-m-d H:i:s', strtotime("+$sla_hours hours"));

$stmt = mysqli_prepare($conn, "INSERT INTO support_tickets 
    (ticket_no, client_email, subject, message, priority, category, status, sla_due, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, 'open', ?, NOW())");

mysqli_stmt_bind_param($stmt, "sssssss", $ticket_no, $user_email, $subject, $message, $priority, $category, $sla_due);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true, 
        'ticket_id' => mysqli_insert_id($conn), 
        'ticket_no' => $ticket_no,
        'message' => 'Ticket created successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>