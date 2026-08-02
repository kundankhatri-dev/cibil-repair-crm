<?php
// ============================================================
// CIBIL REPAIR CRM - Create Support Ticket API
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADERS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = isset($input['email']) ? trim($input['email']) : '';
$subject = isset($input['subject']) ? trim($input['subject']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';

// ============================================================
// VALIDATION
// ============================================================

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

if (empty($subject)) {
    echo json_encode(['success' => false, 'error' => 'Subject is required']);
    exit;
}

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

// ============================================================
# CREATE SUPPORT TICKETS TABLE (if not exists)
// ============================================================

$createTable = "CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client_email (client_email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $createTable);

// ============================================================
# CHECK IF USER EXISTS
// ============================================================

$userCheck = mysqli_query($conn, "SELECT id, name FROM users WHERE email = '$email'");
if (!$userCheck || mysqli_num_rows($userCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'User not found with this email']);
    exit;
}
$user = mysqli_fetch_assoc($userCheck);

// ============================================================
# INSERT TICKET
// ============================================================

$sql = "INSERT INTO support_tickets (client_email, subject, message, status, created_at) 
        VALUES (?, ?, ?, 'open', NOW())";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'sss', $email, $subject, $message);

if (mysqli_stmt_execute($stmt)) {
    $ticket_id = mysqli_insert_id($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Ticket created successfully',
        'data' => [
            'ticket_id' => $ticket_id,
            'email' => $email,
            'subject' => $subject,
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create ticket: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
exit;
?>