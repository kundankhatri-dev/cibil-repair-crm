<?php
// api/client/create_ticket.php - Create a new support ticket
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get client_id (only client can create ticket)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';

// Only client can create tickets
if ($viewer_role !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Only clients can create support tickets']);
    exit;
}

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

// ========== GET INPUT ==========
$subject = trim($input['subject'] ?? '');
$message = trim($input['message'] ?? '');
$priority = trim($input['priority'] ?? 'medium');
$category = trim($input['category'] ?? 'general');
$case_id = isset($input['case_id']) ? (int)$input['case_id'] : null;
$attachment = $input['attachment'] ?? null; // Base64 encoded file

// ========== VALIDATION ==========
$errors = [];

if (empty($subject)) {
    $errors[] = "Subject is required";
} elseif (strlen($subject) < 5) {
    $errors[] = "Subject must be at least 5 characters";
} elseif (strlen($subject) > 200) {
    $errors[] = "Subject must be less than 200 characters";
}

if (empty($message)) {
    $errors[] = "Message is required";
} elseif (strlen($message) < 10) {
    $errors[] = "Please provide more details (minimum 10 characters)";
} elseif (strlen($message) > 5000) {
    $errors[] = "Message must be less than 5000 characters";
}

$valid_priorities = ['low', 'medium', 'high', 'urgent'];
if (!empty($priority) && !in_array($priority, $valid_priorities)) {
    $errors[] = "Invalid priority selected";
}

$valid_categories = ['general', 'technical', 'payment', 'case', 'document', 'dispute', 'feedback', 'other'];
if (!empty($category) && !in_array($category, $valid_categories)) {
    $errors[] = "Invalid category selected";
}

if ($case_id !== null && $case_id <= 0) {
    $errors[] = "Invalid case ID";
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ========== CREATE TICKETS TABLE IF NOT EXISTS ==========
$create_tickets = "CREATE TABLE IF NOT EXISTS support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_no VARCHAR(50) UNIQUE,
    client_id INT NOT NULL,
    case_id INT,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'waiting', 'resolved', 'closed') DEFAULT 'open',
    category VARCHAR(50) DEFAULT 'general',
    assigned_to INT,
    assigned_at DATETIME,
    resolved_at DATETIME,
    closed_at DATETIME,
    rating TINYINT,
    rating_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created (created_at)
)";

mysqli_query($conn, $create_tickets);

// ========== CREATE TICKET REPLIES TABLE ==========
$create_replies = "CREATE TABLE IF NOT EXISTS ticket_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('client', 'agent', 'admin') DEFAULT 'client',
    message TEXT NOT NULL,
    attachment VARCHAR(500),
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket (ticket_id),
    INDEX idx_read (is_read)
)";

mysqli_query($conn, $create_replies);

// ========== CREATE TICKET ATTACHMENTS TABLE ==========
$create_attachments = "CREATE TABLE IF NOT EXISTS ticket_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    file_size INT,
    file_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket (ticket_id)
)";

mysqli_query($conn, $create_attachments);

// ========== GENERATE TICKET NUMBER ==========
function generateTicketNo($conn, $client_id) {
    $prefix = 'TKT';
    $year = date('Y');
    $month = date('m');
    
    $query = "SELECT ticket_no FROM support_tickets WHERE ticket_no LIKE '$prefix$year$month%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_num = (int)substr($row['ticket_no'], -6);
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    
    return $prefix . $year . $month . str_pad($new_num, 6, '0', STR_PAD_LEFT);
}

$ticket_no = generateTicketNo($conn, $client_id);

// ========== HANDLE ATTACHMENT (if any) ==========
$attachment_path = null;
$attachment_name = null;
$attachment_size = null;
$attachment_type = null;

if ($attachment && !empty($attachment)) {
    // Decode base64 attachment
    $attachment_data = base64_decode($attachment);
    if ($attachment_data) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $attachment_data);
        finfo_close($finfo);
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (in_array($mime_type, $allowed_types)) {
            $extension = '';
            switch($mime_type) {
                case 'image/jpeg': $extension = '.jpg'; break;
                case 'image/png': $extension = '.png'; break;
                case 'application/pdf': $extension = '.pdf'; break;
                case 'application/msword': $extension = '.doc'; break;
                case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': $extension = '.docx'; break;
            }
            
            $attachment_name = 'ticket_' . $ticket_no . '_' . time() . $extension;
            $upload_dir = '../uploads/tickets/';
            
            // Create directory if not exists
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $attachment_path = $upload_dir . $attachment_name;
            file_put_contents($attachment_path, $attachment_data);
            $attachment_size = strlen($attachment_data);
            $attachment_type = $mime_type;
        }
    }
}

// ========== INSERT TICKET ==========
$insert_query = "INSERT INTO support_tickets (
                    ticket_no, client_id, case_id, subject, message, 
                    priority, status, category
                ) VALUES (?, ?, ?, ?, ?, ?, 'open', ?)";

$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, 
    "siissss", 
    $ticket_no, $client_id, $case_id, $subject, $message,
    $priority, $category
);

$inserted = mysqli_stmt_execute($insert_stmt);
$ticket_id = mysqli_insert_id($conn);
mysqli_stmt_close($insert_stmt);

if (!$inserted) {
    echo json_encode(['success' => false, 'error' => 'Failed to create ticket. Please try again.']);
    exit;
}

// ========== ADD INITIAL REPLY (the message itself) ==========
$add_reply = mysqli_prepare($conn, "INSERT INTO ticket_replies (ticket_id, user_id, user_type, message) VALUES (?, ?, 'client', ?)");
mysqli_stmt_bind_param($add_reply, "iis", $ticket_id, $client_id, $message);
mysqli_stmt_execute($add_reply);
mysqli_stmt_close($add_reply);

// ========== SAVE ATTACHMENT IF ANY ==========
if ($attachment_path && $attachment_name) {
    $save_attachment = mysqli_prepare($conn, "INSERT INTO ticket_attachments (ticket_id, file_name, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($save_attachment, "issis", $ticket_id, $attachment_name, $attachment_path, $attachment_size, $attachment_type);
    mysqli_stmt_execute($save_attachment);
    mysqli_stmt_close($save_attachment);
}

// ========== CREATE NOTIFICATION ==========
$notification_title = "Support Ticket Created";
$notification_message = "Your ticket #$ticket_no has been created. Our support team will respond within 24 hours.";

$add_notification = mysqli_prepare($conn, "INSERT INTO client_notifications (client_id, notification_type, title, message, link, priority) VALUES (?, 'ticket', ?, ?, ?, 'medium')");
$link = "client-dashboard.php?section=tickets";
mysqli_stmt_bind_param($add_notification, "issss", $client_id, $notification_title, $notification_message, $link);
mysqli_stmt_execute($add_notification);
mysqli_stmt_close($add_notification);

// ========== LOG ACTIVITY ==========
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$log_activity = mysqli_prepare($conn, "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address, user_agent) VALUES (?, 'ticket_created', ?, ?, ?)");
$desc = "Created support ticket #$ticket_no: $subject";
mysqli_stmt_bind_param($log_activity, "isss", $client_id, $desc, $ip_address, $user_agent);
mysqli_stmt_execute($log_activity);
mysqli_stmt_close($log_activity);

// ========== RETURN RESPONSE ==========
$priority_labels = [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
    'urgent' => 'Urgent'
];

$category_labels = [
    'general' => 'General Inquiry',
    'technical' => 'Technical Issue',
    'payment' => 'Payment Related',
    'case' => 'Case Status',
    'document' => 'Document Support',
    'dispute' => 'Dispute Assistance',
    'feedback' => 'Feedback',
    'other' => 'Other'
];

echo json_encode([
    'success' => true,
    'message' => 'Support ticket created successfully',
    'ticket' => [
        'id' => $ticket_id,
        'ticket_no' => $ticket_no,
        'subject' => $subject,
        'priority' => $priority,
        'priority_label' => $priority_labels[$priority],
        'category' => $category,
        'category_label' => $category_labels[$category],
        'status' => 'open',
        'status_label' => 'Open',
        'created_at' => date('Y-m-d H:i:s'),
        'created_at_formatted' => date('d M Y h:i A')
    ],
    'next_steps' => [
        'Our support team will review your ticket',
        'Expected response time: 24 hours',
        'You will receive a notification when there is a reply',
        'You can track ticket status in the Support Tickets section'
    ]
]);

mysqli_close($conn);
?>