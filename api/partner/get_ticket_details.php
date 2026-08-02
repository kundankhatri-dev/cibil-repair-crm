<?php
// api/partner/get_ticket_details.php
// Partner Get Ticket Details API - View support ticket with conversation history

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

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
$role_check = mysqli_prepare($conn, "SELECT role, name FROM users WHERE id = ?");
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
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        INDEX idx_status (status),
        INDEX idx_ticket_no (ticket_no),
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
        FOREIGN KEY (ticket_id) REFERENCES $ticketsTable(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createReplies);
}

// ========== GET INPUT PARAMETERS ==========
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'view';
$reply_message = isset($_POST['reply']) ? trim($_POST['reply']) : '';

if ($ticket_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid Ticket ID is required']);
    exit;
}

// ========== HANDLE REPLY ACTION ==========
if ($action === 'reply' && !empty($reply_message)) {
    $insertReply = mysqli_prepare($conn, "INSERT INTO $repliesTable (ticket_id, user_id, user_type, message, created_at) VALUES (?, ?, 'partner', ?, NOW())");
    mysqli_stmt_bind_param($insertReply, "iis", $ticket_id, $partner_id, $reply_message);
    
    if (mysqli_stmt_execute($insertReply)) {
        // Update ticket status to pending (waiting for admin response)
        $updateTicket = mysqli_prepare($conn, "UPDATE $ticketsTable SET status = 'pending', updated_at = NOW() WHERE id = ?");
        mysqli_stmt_bind_param($updateTicket, "i", $ticket_id);
        mysqli_stmt_execute($updateTicket);
        mysqli_stmt_close($updateTicket);
        
        echo json_encode([
            'success' => true,
            'message' => 'Reply added successfully',
            'reply_id' => mysqli_insert_id($conn)
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add reply: ' . mysqli_error($conn)]);
        exit;
    }
    mysqli_stmt_close($insertReply);
}

// ========== HANDLE STATUS UPDATE ACTION ==========
if ($action === 'update_status') {
    $new_status = isset($_POST['status']) ? $_POST['status'] : '';
    $valid_statuses = ['open', 'pending', 'in_progress', 'resolved', 'closed'];
    
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit;
    }
    
    $updateStatus = mysqli_prepare($conn, "UPDATE $ticketsTable SET status = ?, updated_at = NOW() WHERE id = ? AND partner_id = ?");
    mysqli_stmt_bind_param($updateStatus, "sii", $new_status, $ticket_id, $partner_id);
    
    if (mysqli_stmt_execute($updateStatus)) {
        echo json_encode([
            'success' => true,
            'message' => 'Ticket status updated',
            'new_status' => $new_status
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update status']);
        exit;
    }
    mysqli_stmt_close($updateStatus);
}

// ========== GET TICKET DETAILS ==========
$query = "SELECT 
            t.id,
            t.ticket_no,
            t.subject,
            t.message,
            t.priority,
            t.status,
            DATE_FORMAT(t.created_at, '%d-%m-%Y %h:%i %p') as created_at,
            DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i:%s') as created_raw,
            DATE_FORMAT(t.updated_at, '%d-%m-%Y %h:%i %p') as updated_at,
            DATEDIFF(NOW(), t.created_at) as days_old,
            (SELECT COUNT(*) FROM $repliesTable WHERE ticket_id = t.id) as reply_count
          FROM $ticketsTable t
          WHERE t.id = ? AND t.partner_id = ?";

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

// Mark ticket as read by partner
$markRead = mysqli_prepare($conn, "UPDATE $repliesTable SET is_read = 1 WHERE ticket_id = ? AND user_type = 'admin' AND is_read = 0");
mysqli_stmt_bind_param($markRead, "i", $ticket_id);
mysqli_stmt_execute($markRead);
mysqli_stmt_close($markRead);

// ========== GET TICKET REPLIES ==========
$reply_query = "SELECT 
                  r.id,
                  r.message,
                  r.user_type,
                  r.attachment,
                  DATE_FORMAT(r.created_at, '%d-%m-%Y %h:%i %p') as created_at,
                  DATE_FORMAT(r.created_at, '%Y-%m-%d %H:%i:%s') as created_raw,
                  CASE 
                    WHEN r.user_type = 'admin' THEN 'Support Team'
                    ELSE u.name
                  END as replied_by_name,
                  CASE 
                    WHEN r.user_type = 'admin' THEN 'admin'
                    ELSE 'partner'
                  END as reply_type,
                  r.is_read
                FROM $repliesTable r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.ticket_id = ?
                ORDER BY r.created_at ASC";

$reply_stmt = mysqli_prepare($conn, $reply_query);
mysqli_stmt_bind_param($reply_stmt, "i", $ticket_id);
mysqli_stmt_execute($reply_stmt);
$reply_result = mysqli_stmt_get_result($reply_stmt);
$replies = mysqli_fetch_all($reply_result, MYSQLI_ASSOC);
mysqli_stmt_close($reply_stmt);

// Mark all admin replies as read
$markAllRead = mysqli_prepare($conn, "UPDATE $repliesTable SET is_read = 1 WHERE ticket_id = ? AND user_type = 'admin' AND is_read = 0");
mysqli_stmt_bind_param($markAllRead, "i", $ticket_id);
mysqli_stmt_execute($markAllRead);
mysqli_stmt_close($markAllRead);

// ========== SET BADGE COLORS ==========
$priority_colors = [
    'low' => 'info',
    'medium' => 'warning',
    'high' => 'danger',
    'urgent' => 'danger'
];
$ticket['priority_badge'] = $priority_colors[$ticket['priority']] ?? 'info';
$ticket['priority_label'] = ucfirst($ticket['priority']);

$status_colors = [
    'open' => 'warning',
    'pending' => 'warning',
    'in_progress' => 'info',
    'resolved' => 'success',
    'closed' => 'secondary'
];
$ticket['status_badge'] = $status_colors[$ticket['status']] ?? 'warning';
$ticket['status_label'] = ucfirst(str_replace('_', ' ', $ticket['status']));

// ========== GET ATTACHMENT DETAILS ==========
$has_attachments = false;
$attachments = [];
foreach ($replies as $reply) {
    if (!empty($reply['attachment'])) {
        $has_attachments = true;
        $attachments[] = [
            'reply_id' => $reply['id'],
            'file' => $reply['attachment']
        ];
    }
}

// ========== GET TIME STATISTICS ==========
$first_response_time = null;
$resolution_time = null;

if (!empty($replies)) {
    $firstReply = $replies[0];
    if ($firstReply['reply_type'] === 'admin') {
        $first_response_time = $firstReply['created_raw'];
    }
    
    if ($ticket['status'] === 'resolved' || $ticket['status'] === 'closed') {
        $resolution_time = $ticket['updated_at'];
    }
}

// ========== GET SIMILAR TICKETS ==========
$similarTickets = [];
$similarQuery = "SELECT id, ticket_no, subject, status, created_at 
                 FROM $ticketsTable 
                 WHERE partner_id = ? AND id != ? AND status IN ('open', 'pending')
                 ORDER BY created_at DESC LIMIT 3";
$similarStmt = mysqli_prepare($conn, $similarQuery);
mysqli_stmt_bind_param($similarStmt, "ii", $partner_id, $ticket_id);
mysqli_stmt_execute($similarStmt);
$similarResult = mysqli_stmt_get_result($similarStmt);
$similarTickets = mysqli_fetch_all($similarResult, MYSQLI_ASSOC);
mysqli_stmt_close($similarStmt);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'ticket' => $ticket,
    'replies' => $replies,
    'total_replies' => count($replies),
    'total_unread' => count(array_filter($replies, function($r) { return $r['is_read'] == 0 && $r['user_type'] == 'admin'; })),
    'has_attachments' => $has_attachments,
    'attachments' => $attachments,
    'timeline' => [
        'created_at_raw' => $ticket['created_raw'],
        'first_response_at' => $first_response_time,
        'resolution_at' => $resolution_time,
        'days_old' => (int)$ticket['days_old']
    ],
    'similar_tickets' => $similarTickets,
    'can_reply' => !in_array($ticket['status'], ['resolved', 'closed']),
    'can_close' => $ticket['status'] !== 'closed',
    'can_reopen' => in_array($ticket['status'], ['resolved', 'closed']),
    'last_updated' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>