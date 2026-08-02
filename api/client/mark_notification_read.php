<?php
// api/client/mark_notification_read.php - Mark notification(s) as read
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
$notification_id = isset($input['notification_id']) ? (int)$input['notification_id'] : 0;
$mark_all = isset($input['mark_all']) ? (bool)$input['mark_all'] : false;
$mark_by_type = isset($input['notification_type']) ? trim($input['notification_type']) : null;

// ========== VALIDATION ==========
if (!$mark_all && $notification_id <= 0 && !$mark_by_type) {
    echo json_encode(['success' => false, 'error' => 'Notification ID, type, or mark_all flag is required']);
    exit;
}

// ========== MARK SINGLE NOTIFICATION AS READ ==========
if ($notification_id > 0 && !$mark_all) {
    // Check if notification exists and belongs to client
    $check_query = "SELECT id, title, notification_type FROM client_notifications WHERE id = ? AND client_id = ? AND is_read = 0";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "ii", $notification_id, $client_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $notification = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);
    
    if (!$notification) {
        echo json_encode(['success' => false, 'error' => 'Notification not found or already read']);
        exit;
    }
    
    // Mark as read
    $update_query = "UPDATE client_notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND client_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "ii", $notification_id, $client_id);
    $updated = mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    if ($updated) {
        $message = "Notification marked as read";
        $marked_count = 1;
        $marked_type = $notification['notification_type'];
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to mark notification as read']);
        exit;
    }
}

// ========== MARK ALL NOTIFICATIONS AS READ ==========
elseif ($mark_all) {
    $update_query = "UPDATE client_notifications SET is_read = 1, read_at = NOW() WHERE client_id = ? AND is_read = 0";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "i", $client_id);
    $updated = mysqli_stmt_execute($update_stmt);
    $affected_rows = mysqli_stmt_affected_rows($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    if ($updated) {
        $message = "All notifications marked as read";
        $marked_count = $affected_rows;
        $marked_type = 'all';
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to mark notifications as read']);
        exit;
    }
}

// ========== MARK BY NOTIFICATION TYPE ==========
elseif ($mark_by_type) {
    $valid_types = ['case_update', 'payment', 'document', 'dispute', 'ticket', 'promotion', 'score_update', 'system'];
    
    if (!in_array($mark_by_type, $valid_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid notification type']);
        exit;
    }
    
    $update_query = "UPDATE client_notifications SET is_read = 1, read_at = NOW() WHERE client_id = ? AND notification_type = ? AND is_read = 0";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "is", $client_id, $mark_by_type);
    $updated = mysqli_stmt_execute($update_stmt);
    $affected_rows = mysqli_stmt_affected_rows($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    if ($updated) {
        $type_labels = [
            'case_update' => 'case updates',
            'payment' => 'payment notifications',
            'document' => 'document notifications',
            'dispute' => 'dispute notifications',
            'ticket' => 'ticket notifications',
            'promotion' => 'promotions',
            'score_update' => 'score updates',
            'system' => 'system notifications'
        ];
        $message = "All " . ($type_labels[$mark_by_type] ?? $mark_by_type) . " marked as read";
        $marked_count = $affected_rows;
        $marked_type = $mark_by_type;
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to mark notifications as read']);
        exit;
    }
}

// ========== GET UPDATED UNREAD COUNTS ==========
$count_query = "SELECT 
                    COUNT(*) as unread_count,
                    SUM(CASE WHEN notification_type = 'case_update' THEN 1 ELSE 0 END) as case_update_unread,
                    SUM(CASE WHEN notification_type = 'payment' THEN 1 ELSE 0 END) as payment_unread,
                    SUM(CASE WHEN notification_type = 'document' THEN 1 ELSE 0 END) as document_unread,
                    SUM(CASE WHEN notification_type = 'dispute' THEN 1 ELSE 0 END) as dispute_unread,
                    SUM(CASE WHEN notification_type = 'ticket' THEN 1 ELSE 0 END) as ticket_unread,
                    SUM(CASE WHEN priority = 'high' AND is_read = 0 THEN 1 ELSE 0 END) as high_priority_unread
                FROM client_notifications 
                WHERE client_id = ? AND is_read = 0";

$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $client_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$counts = mysqli_fetch_assoc($count_result);
mysqli_stmt_close($count_stmt);

// ========== LOG ACTIVITY (only for single notification or mark all) ==========
if ($notification_id > 0 || $mark_all) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $log_activity = mysqli_prepare($conn, "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address, user_agent) VALUES (?, 'notification_read', ?, ?, ?)");
    $desc = $notification_id > 0 ? "Marked notification #$notification_id as read" : "Marked all notifications as read ($marked_count items)";
    mysqli_stmt_bind_param($log_activity, "isss", $client_id, $desc, $ip_address, $user_agent);
    mysqli_stmt_execute($log_activity);
    mysqli_stmt_close($log_activity);
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'message' => $message,
    'marked_count' => $marked_count ?? 0,
    'marked_type' => $marked_type ?? null,
    'unread_counts' => [
        'total' => (int)($counts['unread_count'] ?? 0),
        'case_update' => (int)($counts['case_update_unread'] ?? 0),
        'payment' => (int)($counts['payment_unread'] ?? 0),
        'document' => (int)($counts['document_unread'] ?? 0),
        'dispute' => (int)($counts['dispute_unread'] ?? 0),
        'ticket' => (int)($counts['ticket_unread'] ?? 0),
        'high_priority' => (int)($counts['high_priority_unread'] ?? 0)
    ]
]);

mysqli_close($conn);
?>