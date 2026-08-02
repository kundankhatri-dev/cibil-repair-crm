<?php
// api/client/get_ticket_details.php - Get single ticket with all replies
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
$viewer_id = $_SESSION['user_id'] ?? null;

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get ticket ID
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ticket_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ticket ID']);
    exit;
}

// ========== GET TICKET DETAILS ==========
$ticket_query = "SELECT 
                    t.*,
                    DATE_FORMAT(t.created_at, '%d %b %Y') as created_date_formatted,
                    DATE_FORMAT(t.created_at, '%h:%i %p') as created_time,
                    DATE_FORMAT(t.updated_at, '%d %b %Y %h:%i %p') as updated_at_formatted,
                    DATE_FORMAT(t.resolved_at, '%d %b %Y %h:%i %p') as resolved_at_formatted,
                    DATE_FORMAT(t.closed_at, '%d %b %Y %h:%i %p') as closed_at_formatted,
                    u.name as client_name,
                    u.email as client_email,
                    u.phone as client_phone
                FROM support_tickets t
                LEFT JOIN users u ON t.client_id = u.id
                WHERE t.id = ? AND t.client_id = ?";

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

// ========== GET ALL REPLIES ==========
$replies_query = "SELECT 
                    r.*,
                    DATE_FORMAT(r.created_at, '%d %b %Y') as created_date_formatted,
                    DATE_FORMAT(r.created_at, '%h:%i %p') as created_time,
                    DATE_FORMAT(r.created_at, '%d %b %Y %h:%i %p') as created_at_formatted,
                    u.name as user_name,
                    u.email as user_email
                FROM ticket_replies r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.ticket_id = ?
                ORDER BY r.created_at ASC";

$replies_stmt = mysqli_prepare($conn, $replies_query);
mysqli_stmt_bind_param($replies_stmt, "i", $ticket_id);
mysqli_stmt_execute($replies_stmt);
$replies_result = mysqli_stmt_get_result($replies_stmt);
$replies = mysqli_fetch_all($replies_result, MYSQLI_ASSOC);
mysqli_stmt_close($replies_stmt);

// ========== GET ATTACHMENTS ==========
$attachments_query = "SELECT id, file_name, file_path, file_size, file_type, uploaded_at,
                        CONCAT(ROUND(file_size / 1024, 1), ' KB') as size_formatted
                      FROM ticket_attachments 
                      WHERE ticket_id = ?
                      ORDER BY uploaded_at DESC";

$attachments_stmt = mysqli_prepare($conn, $attachments_query);
mysqli_stmt_bind_param($attachments_stmt, "i", $ticket_id);
mysqli_stmt_execute($attachments_stmt);
$attachments_result = mysqli_stmt_get_result($attachments_stmt);
$attachments = mysqli_fetch_all($attachments_result, MYSQLI_ASSOC);
mysqli_stmt_close($attachments_stmt);

// ========== MARK UNREAD REPLIES AS READ ==========
// When client views ticket, mark all agent replies as read
if ($viewer_role === 'client') {
    $mark_read_query = "UPDATE ticket_replies SET is_read = 1 WHERE ticket_id = ? AND user_type != 'client' AND is_read = 0";
    $mark_stmt = mysqli_prepare($conn, $mark_read_query);
    mysqli_stmt_bind_param($mark_stmt, "i", $ticket_id);
    mysqli_stmt_execute($mark_stmt);
    mysqli_stmt_close($mark_stmt);
}

// ========== GET RELATED TICKETS (SAME CASE OR SIMILAR) ==========
$related_query = "SELECT id, ticket_no, subject, status, created_at 
                  FROM support_tickets 
                  WHERE client_id = ? AND id != ? 
                  ORDER BY created_at DESC LIMIT 3";

$related_stmt = mysqli_prepare($conn, $related_query);
mysqli_stmt_bind_param($related_stmt, "ii", $client_id, $ticket_id);
mysqli_stmt_execute($related_stmt);
$related_result = mysqli_stmt_get_result($related_stmt);
$related_tickets = mysqli_fetch_all($related_result, MYSQLI_ASSOC);
mysqli_stmt_close($related_stmt);

// ========== CALCULATE RESPONSE TIME ==========
$response_time = null;
$first_reply = null;

foreach ($replies as $reply) {
    if ($reply['user_type'] !== 'client' && !$first_reply) {
        $first_reply = $reply['created_at'];
        break;
    }
}

if ($first_reply && $ticket['created_at']) {
    $created = strtotime($ticket['created_at']);
    $first_reply_time = strtotime($first_reply);
    $hours = round(($first_reply_time - $created) / 3600, 1);
    $response_time = $hours;
}

// ========== FORMAT TICKET DATA ==========
$status_labels = [
    'open' => 'Open',
    'in_progress' => 'In Progress',
    'waiting' => 'Waiting for Response',
    'resolved' => 'Resolved',
    'closed' => 'Closed'
];

$status_colors = [
    'open' => 'danger',
    'in_progress' => 'info',
    'waiting' => 'warning',
    'resolved' => 'success',
    'closed' => 'secondary'
];

$priority_labels = [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
    'urgent' => 'Urgent'
];

$priority_colors = [
    'low' => 'secondary',
    'medium' => 'info',
    'high' => 'warning',
    'urgent' => 'danger'
];

$ticket['status_label'] = $status_labels[$ticket['status']] ?? ucfirst($ticket['status']);
$ticket['status_badge'] = $status_colors[$ticket['status']] ?? 'secondary';
$ticket['priority_label'] = $priority_labels[$ticket['priority']] ?? ucfirst($ticket['priority']);
$ticket['priority_badge'] = $priority_colors[$ticket['priority']] ?? 'secondary';
$ticket['response_time_hours'] = $response_time;
$ticket['response_time_formatted'] = $response_time ? $response_time . ' hour(s)' : 'Not yet responded';

// ========== FORMAT REPLIES ==========
foreach ($replies as &$reply) {
    $reply['user_type_label'] = $reply['user_type'] === 'client' ? 'You' : 'Support Team';
    $reply['user_type_icon'] = $reply['user_type'] === 'client' ? 'fa-user' : 'fa-headset';
    $reply['user_type_color'] = $reply['user_type'] === 'client' ? '#0d9e78' : '#2563eb';
    $reply['is_read_label'] = $reply['is_read'] ? 'Read' : 'Unread';
}

// ========== CHECK IF CAN BE REOPENED ==========
$can_reopen = in_array($ticket['status'], ['resolved', 'closed']) && 
              (strtotime($ticket['resolved_at'] ?? $ticket['updated_at']) > strtotime('-7 days'));

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'ticket' => $ticket,
    'replies' => $replies,
    'attachments' => $attachments,
    'related_tickets' => $related_tickets,
    'stats' => [
        'total_replies' => count($replies),
        'total_attachments' => count($attachments),
        'client_replies' => count(array_filter($replies, function($r) { return $r['user_type'] === 'client'; })),
        'agent_replies' => count(array_filter($replies, function($r) { return $r['user_type'] !== 'client'; })),
        'unread_count' => count(array_filter($replies, function($r) { return $r['is_read'] == 0 && $r['user_type'] !== 'client'; }))
    ],
    'actions' => [
        'can_reply' => !in_array($ticket['status'], ['resolved', 'closed']),
        'can_reopen' => $can_reopen,
        'can_close' => !in_array($ticket['status'], ['resolved', 'closed']),
        'can_resolve' => !in_array($ticket['status'], ['resolved', 'closed'])
    ]
]);

mysqli_close($conn);
?>