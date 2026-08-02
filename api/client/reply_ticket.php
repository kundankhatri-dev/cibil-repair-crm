<?php
// api/client/reply_ticket.php - Reply to a support ticket
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

// Get client_id
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';

// Only client can reply to their own tickets
if ($viewer_role !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Only clients can reply to tickets']);
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
$ticket_id = isset($input['ticket_id']) ? (int)$input['ticket_id'] : 0;
$message = trim($input['message'] ?? '');
$attachment = $input['attachment'] ?? null; // Base64 encoded file

// ========== VALIDATION ==========
$errors = [];

if ($ticket_id <= 0) {
    $errors[] = "Invalid ticket ID";
}

if (empty($message)) {
    $errors[] = "Reply message is required";
} elseif (strlen($message) < 3) {
    $errors[] = "Message must be at least 3 characters";
} elseif (strlen($message) > 5000) {
    $errors[] = "Message must be less than 5000 characters";
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ========== CHECK IF TICKET EXISTS AND BELONGS TO CLIENT ==========
$ticket_query = "SELECT id, ticket_no, subject, status, client_id FROM support_tickets WHERE id = ? AND client_id = ?";
$ticket_stmt = mysqli_prepare($conn, $ticket_query);
mysqli_stmt_bind_param($ticket_stmt, "ii", $ticket_id, $client_id);
mysqli_stmt_execute($ticket_stmt);
$ticket_result = mysqli_stmt_get_result($ticket_stmt);
$ticket = mysqli_fetch_assoc($ticket_result);
mysqli_stmt_close($ticket_stmt);

if (!$ticket) {
    echo json_encode(['success' => false, 'error' => 'Ticket not found or access denied']);
    exit;
}

// ========== CHECK IF TICKET CAN BE REPLIED TO ==========
if (in_array($ticket['status'], ['resolved', 'closed'])) {
    echo json_encode(['success' => false, 'error' => 'This ticket is ' . $ticket['status'] . '. Cannot add reply.']);
    exit;
}

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
            
            $attachment_name = 'reply_' . $ticket['ticket_no'] . '_' . time() . $extension;
            $upload_dir = '../uploads/tickets/';
            
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

// ========== INSERT REPLY ==========
$insert_query = "INSERT INTO ticket_replies (ticket_id, user_id, user_type, message, attachment) VALUES (?, ?, 'client', ?, ?)";
$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, "iiss", $ticket_id, $client_id, $message, $attachment_path);
$inserted = mysqli_stmt_execute($insert_stmt);
$reply_id = mysqli_insert_id($conn);
mysqli_stmt_close($insert_stmt);

if (!$inserted) {
    // Clean up attachment if insert failed
    if ($attachment_path && file_exists($attachment_path)) {
        unlink($attachment_path);
    }
    echo json_encode(['success' => false, 'error' => 'Failed to add reply. Please try again.']);
    exit;
}

// ========== SAVE ATTACHMENT RECORD IF ANY ==========
if ($attachment_path && $attachment_name) {
    $save_attachment = mysqli_prepare($conn, "INSERT INTO ticket_attachments (ticket_id, file_name, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($save_attachment, "issis", $ticket_id, $attachment_name, $attachment_path, $attachment_size, $attachment_type);
    mysqli_stmt_execute($save_attachment);
    mysqli_stmt_close($save_attachment);
}

// ========== UPDATE TICKET STATUS ==========
// If ticket was 'waiting', change back to 'in_progress' or 'open'
$new_status = 'open';
if ($ticket['status'] === 'waiting') {
    $new_status = 'in_progress';
}

$update_ticket = "UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_ticket);
mysqli_stmt_bind_param($update_stmt, "si", $new_status, $ticket_id);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

// ========== CREATE NOTIFICATION FOR AGENTS ==========
// In a real system, this would notify support agents
// For now, we'll log it
$notification_title = "New Reply on Ticket #{$ticket['ticket_no']}";
$notification_message = "Client has replied to ticket: {$ticket['subject']}";

// ========== CREATE NOTIFICATION FOR CLIENT (confirmation) ==========
$client_notification_title = "Reply Sent";
$client_notification_message = "Your reply to ticket #{$ticket['ticket_no']} has been sent. Support team will review it shortly.";

$add_notification = mysqli_prepare($conn, "INSERT INTO client_notifications (client_id, notification_type, title, message, link, priority) VALUES (?, 'ticket', ?, ?, ?, 'medium')");
$link = "client-dashboard.php?section=tickets&id={$ticket_id}";
mysqli_stmt_bind_param($add_notification, "issss", $client_id, $client_notification_title, $client_notification_message, $link);
mysqli_stmt_execute($add_notification);
mysqli_stmt_close($add_notification);

// ========== LOG ACTIVITY ==========
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$log_activity = mysqli_prepare($conn, "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address, user_agent) VALUES (?, 'ticket_reply', ?, ?, ?)");
$desc = "Replied to ticket #{$ticket['ticket_no']}: {$ticket['subject']}";
mysqli_stmt_bind_param($log_activity, "isss", $client_id, $desc, $ip_address, $user_agent);
mysqli_stmt_execute($log_activity);
mysqli_stmt_close($log_activity);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'message' => 'Reply sent successfully',
    'reply' => [
        'id' => $reply_id,
        'message' => substr($message, 0, 200) . (strlen($message) > 200 ? '...' : ''),
        'created_at' => date('Y-m-d H:i:s'),
        'created_at_formatted' => date('d M Y h:i A'),
        'has_attachment' => !empty($attachment_path),
        'attachment_name' => $attachment_name
    ],
    'ticket' => [
        'id' => $ticket_id,
        'ticket_no' => $ticket['ticket_no'],
        'status' => $new_status,
        'status_label' => ucfirst(str_replace('_', ' ', $new_status))
    ],
    'next_steps' => [
        'Support team will review your reply',
        'You will be notified when there is a response',
        'Expected response time: 24 hours'
    ]
]);

mysqli_close($conn);
?>