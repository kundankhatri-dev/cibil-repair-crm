<?php
// api/partner/reply_ticket.php
// Partner Reply Ticket API - Add reply to support ticket

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role, name, email FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$partner_name = $role_data['name'];
$partner_email = $role_data['email'];

// ========== ENSURE TABLES EXIST ==========
$ticketsTable = 'partner_tickets';
$checkTicketsTable = mysqli_query($conn, "SHOW TABLES LIKE '$ticketsTable'");
if (mysqli_num_rows($checkTicketsTable) == 0) {
    $createTickets = "CREATE TABLE IF NOT EXISTS $ticketsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        ticket_no VARCHAR(20) NOT NULL UNIQUE,
        subject VARCHAR(255) NOT NULL,
        message TEXT,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        status ENUM('open', 'pending', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
        reply_count INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        INDEX idx_status (status),
        FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createTickets);
}

$repliesTable = 'partner_ticket_replies';
$checkRepliesTable = mysqli_query($conn, "SHOW TABLES LIKE '$repliesTable'");
if (mysqli_num_rows($checkRepliesTable) == 0) {
    $createReplies = "CREATE TABLE IF NOT EXISTS $repliesTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        user_id INT NOT NULL,
        user_type ENUM('partner', 'admin') DEFAULT 'partner',
        message TEXT NOT NULL,
        attachment VARCHAR(500) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ticket_id (ticket_id),
        INDEX idx_user_id (user_id),
        FOREIGN KEY (ticket_id) REFERENCES $ticketsTable(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createReplies);
}

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);
$ticket_id = isset($data['ticket_id']) ? (int)$data['ticket_id'] : 0;
$message = trim($data['message'] ?? '');

// Handle file upload
$attachment = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../uploads/tickets/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $_FILES['attachment']['name']);
    $file_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
        $attachment = 'uploads/tickets/' . $file_name;
    }
}

// ========== VALIDATE INPUT ==========
if ($ticket_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid Ticket ID is required']);
    exit;
}

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Reply message is required']);
    exit;
}

$message_length = strlen($message);
if ($message_length < 5) {
    echo json_encode(['success' => false, 'error' => 'Message must be at least 5 characters']);
    exit;
}

if ($message_length > 5000) {
    echo json_encode(['success' => false, 'error' => 'Message is too long (maximum 5000 characters)']);
    exit;
}

// ========== VERIFY TICKET BELONGS TO PARTNER ==========
$query = "SELECT id, ticket_no, subject, status FROM $ticketsTable WHERE id = ? AND partner_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $ticket_id, $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ticket = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$ticket) {
    echo json_encode(['success' => false, 'error' => 'Ticket not found or access denied']);
    exit;
}

// Check if ticket is closed or resolved
if (in_array($ticket['status'], ['closed', 'resolved'])) {
    echo json_encode(['success' => false, 'error' => 'Cannot reply to a closed/resolved ticket. Please create a new ticket.']);
    exit;
}

// ========== INSERT REPLY ==========
$insert_query = "INSERT INTO $repliesTable (ticket_id, user_id, user_type, message, attachment, created_at) VALUES (?, ?, 'partner', ?, ?, NOW())";
$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, "iiss", $ticket_id, $partner_id, $message, $attachment);

if (mysqli_stmt_execute($insert_stmt)) {
    $reply_id = mysqli_insert_id($conn);
    
    // Update ticket status and reply count
    $update_query = "UPDATE $ticketsTable SET status = 'pending', reply_count = reply_count + 1, updated_at = NOW() WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "i", $ticket_id);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    // Log activity
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'reply_ticket', ?, NOW())");
        if ($log_stmt) {
            $description = "Replied to ticket #" . $ticket['ticket_no'] . ": " . substr($message, 0, 100);
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
    // Send notification to admin (optional - can be implemented)
    // $admin_email = "admin@cibilrepair.com";
    // $subject = "New Reply on Ticket #" . $ticket['ticket_no'];
    // $email_message = "Partner $partner_name has replied to ticket #{$ticket['ticket_no']}\n\nReply: $message";
    // mail($admin_email, $subject, $email_message);
    
    echo json_encode([
        'success' => true,
        'message' => 'Reply added successfully',
        'reply_id' => $reply_id,
        'ticket_id' => $ticket_id,
        'ticket_no' => $ticket['ticket_no'],
        'ticket_status' => 'pending',
        'has_attachment' => !empty($attachment),
        'attachment' => $attachment
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add reply: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($insert_stmt);
mysqli_close($conn);
?>