<?php
// api/partner/create_ticket.php
// Partner Create Ticket API - Create a new support ticket

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');

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

// Verify user is actually a partner and get details
$role_check = mysqli_prepare($conn, "SELECT role, name, email, phone FROM users WHERE id = ?");
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
$partner_email = $role_data['email'] ?? '';
$partner_phone = $role_data['phone'] ?? '';

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);

$subject = trim($data['subject'] ?? '');
$message = trim($data['message'] ?? '');
$priority = trim($data['priority'] ?? 'medium');

// ========== VALIDATE INPUTS ==========
// Validate subject
if (empty($subject)) {
    echo json_encode(['success' => false, 'error' => 'Subject is required']);
    exit;
}

if (strlen($subject) < 5) {
    echo json_encode(['success' => false, 'error' => 'Subject must be at least 5 characters']);
    exit;
}

if (strlen($subject) > 255) {
    echo json_encode(['success' => false, 'error' => 'Subject is too long (maximum 255 characters)']);
    exit;
}

// Validate message
if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

$message_length = strlen($message);
if ($message_length < 10) {
    echo json_encode(['success' => false, 'error' => 'Please provide a detailed message (minimum 10 characters)']);
    exit;
}

if ($message_length > 5000) {
    echo json_encode(['success' => false, 'error' => 'Message is too long (maximum 5000 characters)']);
    exit;
}

// Validate priority
$valid_priorities = ['low', 'medium', 'high', 'urgent'];
if (!in_array($priority, $valid_priorities)) {
    $priority = 'medium';
}

// ========== ENSURE TABLES EXIST ==========
$tickets_table = 'partner_tickets';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$tickets_table'");
if (mysqli_num_rows($checkTable) == 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS $tickets_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        ticket_no VARCHAR(20) NOT NULL UNIQUE,
        subject VARCHAR(255) NOT NULL,
        message TEXT,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        status ENUM('open', 'pending', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        INDEX idx_status (status),
        INDEX idx_priority (priority),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_table);
}

// Create replies table if not exists
$replies_table = 'partner_ticket_replies';
$checkRepliesTable = mysqli_query($conn, "SHOW TABLES LIKE '$replies_table'");
if (mysqli_num_rows($checkRepliesTable) == 0) {
    $create_replies = "CREATE TABLE IF NOT EXISTS $replies_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        user_id INT NOT NULL,
        user_type ENUM('partner', 'admin') DEFAULT 'partner',
        message TEXT NOT NULL,
        attachment VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ticket_id (ticket_id),
        INDEX idx_user_id (user_id),
        FOREIGN KEY (ticket_id) REFERENCES $tickets_table(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_replies);
}

// ========== GENERATE UNIQUE TICKET NUMBER ==========
function generateUniqueTicketNumber($conn, $table) {
    $prefix = 'TKT';
    $year = date('Y');
    $month = date('m');
    
    // Get last ticket number for this month
    $lastQuery = "SELECT ticket_no FROM $table WHERE ticket_no LIKE '$prefix$year$month%' ORDER BY id DESC LIMIT 1";
    $lastResult = mysqli_query($conn, $lastQuery);
    
    if ($lastResult && mysqli_num_rows($lastResult) > 0) {
        $lastTicket = mysqli_fetch_assoc($lastResult);
        $lastNumber = (int)substr($lastTicket['ticket_no'], -4);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    $ticket_no = $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    
    // Verify uniqueness
    $checkStmt = mysqli_prepare($conn, "SELECT id FROM $table WHERE ticket_no = ?");
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "s", $ticket_no);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            // If duplicate, add random suffix
            $ticket_no = $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT) . rand(10, 99);
        }
        mysqli_stmt_close($checkStmt);
    }
    
    return $ticket_no;
}

$ticket_no = generateUniqueTicketNumber($conn, $tickets_table);

// ========== CHECK FOR RECENT DUPLICATE TICKETS ==========
$checkRecentStmt = mysqli_prepare($conn, "SELECT id, created_at FROM $tickets_table 
                                          WHERE partner_id = ? AND subject = ? 
                                          AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
if ($checkRecentStmt) {
    mysqli_stmt_bind_param($checkRecentStmt, "is", $partner_id, $subject);
    mysqli_stmt_execute($checkRecentStmt);
    $recentResult = mysqli_stmt_get_result($checkRecentStmt);
    if (mysqli_num_rows($recentResult) > 0) {
        echo json_encode(['success' => false, 'error' => 'You have already created a similar ticket recently. Please wait before creating another.']);
        exit;
    }
    mysqli_stmt_close($checkRecentStmt);
}

// ========== INSERT TICKET ==========
$query = "INSERT INTO $tickets_table (partner_id, ticket_no, subject, message, priority, status, created_at) 
          VALUES (?, ?, ?, ?, ?, 'open', NOW())";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "issss", $partner_id, $ticket_no, $subject, $message, $priority);

if (mysqli_stmt_execute($stmt)) {
    $ticket_id = mysqli_insert_id($conn);
    
    // Insert the first reply (the ticket message) into replies table
    $replyStmt = mysqli_prepare($conn, "INSERT INTO $replies_table (ticket_id, user_id, user_type, message, created_at) 
                                        VALUES (?, ?, 'partner', ?, NOW())");
    if ($replyStmt) {
        mysqli_stmt_bind_param($replyStmt, "iis", $ticket_id, $partner_id, $message);
        mysqli_stmt_execute($replyStmt);
        mysqli_stmt_close($replyStmt);
    }
    
    // Log activity
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'create_ticket', ?, NOW())");
        if ($log_stmt) {
            $description = "Created support ticket: " . substr($subject, 0, 50) . " (Ticket #$ticket_no)";
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
    // Send notification (commented - implement your email/SMS system)
    // $to = 'support@cibilrepair.com';
    // $email_subject = "New Support Ticket: $ticket_no - $subject";
    // $email_message = "Partner: $partner_name\nTicket #: $ticket_no\nSubject: $subject\nPriority: $priority\n\nMessage:\n$message";
    // mail($to, $email_subject, $email_message);
    
    echo json_encode([
        'success' => true,
        'ticket_no' => $ticket_no,
        'ticket_id' => $ticket_id,
        'subject' => $subject,
        'priority' => $priority,
        'priority_label' => ucfirst($priority),
        'created_at' => date('Y-m-d H:i:s'),
        'created_at_formatted' => date('d M Y, h:i A'),
        'message' => 'Ticket created successfully. Our support team will respond shortly.',
        'reply_count' => 1
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
}

// ========== CLEAN UP ==========
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($role_check)) mysqli_stmt_close($role_check);

mysqli_close($conn);
?>