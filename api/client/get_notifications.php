<?php
// api/client/get_notifications.php - Get all notifications for client
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

// Get client_id (supports both client and partner/admin viewing)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';
$viewer_id = $_SESSION['user_id'] ?? null;

// If partner or admin viewing, allow client_id from GET
if (in_array($viewer_role, ['admin', 'partner']) && isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
    
    // Verify partner has access to this client
    if ($viewer_role === 'partner' && $viewer_id) {
        $check = mysqli_prepare($conn, "SELECT COUNT(*) FROM leads WHERE partner_id = ? AND customer_id = ?");
        mysqli_stmt_bind_param($check, "ii", $viewer_id, $client_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_bind_result($check, $count);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);
        
        if ($count == 0) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
    }
}

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// ========== CREATE NOTIFICATIONS TABLE ==========
$create_table = "CREATE TABLE IF NOT EXISTS client_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(500),
    icon VARCHAR(50),
    color VARCHAR(20),
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    is_read TINYINT DEFAULT 0,
    is_archived TINYINT DEFAULT 0,
    read_at DATETIME,
    archived_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    INDEX idx_client (client_id),
    INDEX idx_read (is_read),
    INDEX idx_type (notification_type),
    INDEX idx_created (created_at),
    INDEX idx_expires (expires_at)
)";

mysqli_query($conn, $create_table);

// ========== CREATE NOTIFICATION PREFERENCES TABLE ==========
$create_prefs = "CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL UNIQUE,
    email_case_update TINYINT DEFAULT 1,
    email_payment TINYINT DEFAULT 1,
    email_document TINYINT DEFAULT 1,
    email_dispute TINYINT DEFAULT 1,
    email_ticket TINYINT DEFAULT 1,
    email_promotion TINYINT DEFAULT 0,
    sms_case_update TINYINT DEFAULT 0,
    sms_payment TINYINT DEFAULT 1,
    sms_document TINYINT DEFAULT 0,
    sms_dispute TINYINT DEFAULT 0,
    sms_ticket TINYINT DEFAULT 0,
    push_case_update TINYINT DEFAULT 1,
    push_payment TINYINT DEFAULT 1,
    push_document TINYINT DEFAULT 1,
    push_dispute TINYINT DEFAULT 1,
    push_ticket TINYINT DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client (client_id)
)";

mysqli_query($conn, $create_prefs);

// Insert default preferences if not exists
$check_prefs = mysqli_prepare($conn, "SELECT id FROM notification_preferences WHERE client_id = ?");
mysqli_stmt_bind_param($check_prefs, "i", $client_id);
mysqli_stmt_execute($check_prefs);
$prefs_result = mysqli_stmt_get_result($check_prefs);
if (!mysqli_fetch_assoc($prefs_result)) {
    $insert_prefs = mysqli_prepare($conn, "INSERT INTO notification_preferences (client_id) VALUES (?)");
    mysqli_stmt_bind_param($insert_prefs, "i", $client_id);
    mysqli_stmt_execute($insert_prefs);
    mysqli_stmt_close($insert_prefs);
}
mysqli_stmt_close($check_prefs);

// Get filter parameters
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$read_filter = isset($_GET['read']) ? trim($_GET['read']) : 'all';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if ($limit < 1 || $limit > 100) {
    $limit = 30;
}

// ========== DELETE EXPIRED NOTIFICATIONS ==========
$delete_expired = "DELETE FROM client_notifications WHERE expires_at IS NOT NULL AND expires_at < NOW()";
mysqli_query($conn, $delete_expired);

// ========== BUILD QUERY ==========
$query = "SELECT 
            n.*,
            DATE_FORMAT(n.created_at, '%d %b %Y') as created_date_formatted,
            DATE_FORMAT(n.created_at, '%h:%i %p') as created_time,
            DATE_FORMAT(n.read_at, '%d %b %Y %h:%i %p') as read_at_formatted,
            CASE 
                WHEN n.created_at >= NOW() - INTERVAL 1 HOUR THEN 'Just now'
                WHEN n.created_at >= NOW() - INTERVAL 24 HOUR THEN CONCAT(FLOOR(HOUR(TIMEDIFF(NOW(), n.created_at))), ' hours ago')
                WHEN n.created_at >= NOW() - INTERVAL 7 DAY THEN CONCAT(DATEDIFF(NOW(), n.created_at), ' days ago')
                ELSE DATE_FORMAT(n.created_at, '%d %b %Y')
            END as time_ago
          FROM client_notifications n
          WHERE n.client_id = ? AND n.is_archived = 0";

$params = [$client_id];
$types = "i";

if ($type_filter !== 'all') {
    $query .= " AND n.notification_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if ($read_filter === 'unread') {
    $query .= " AND n.is_read = 0";
} elseif ($read_filter === 'read') {
    $query .= " AND n.is_read = 1";
}

$query .= " ORDER BY n.priority DESC, n.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== GET NOTIFICATION COUNTS ==========
$count_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count,
                    SUM(CASE WHEN notification_type = 'case_update' THEN 1 ELSE 0 END) as case_updates,
                    SUM(CASE WHEN notification_type = 'payment' THEN 1 ELSE 0 END) as payment_notifications,
                    SUM(CASE WHEN notification_type = 'document' THEN 1 ELSE 0 END) as document_notifications,
                    SUM(CASE WHEN notification_type = 'dispute' THEN 1 ELSE 0 END) as dispute_notifications,
                    SUM(CASE WHEN notification_type = 'ticket' THEN 1 ELSE 0 END) as ticket_notifications,
                    SUM(CASE WHEN notification_type = 'promotion' THEN 1 ELSE 0 END) as promotions,
                    SUM(CASE WHEN priority = 'high' AND is_read = 0 THEN 1 ELSE 0 END) as high_priority_unread
                FROM client_notifications 
                WHERE client_id = ? AND is_archived = 0";

$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $client_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$counts = mysqli_fetch_assoc($count_result);
mysqli_stmt_close($count_stmt);

// ========== GET GROUPED NOTIFICATIONS BY DATE ==========
$grouped_query = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count
                  FROM client_notifications 
                  WHERE client_id = ? AND is_archived = 0
                  GROUP BY DATE(created_at)
                  ORDER BY date DESC
                  LIMIT 30";

$grouped_stmt = mysqli_prepare($conn, $grouped_query);
mysqli_stmt_bind_param($grouped_stmt, "i", $client_id);
mysqli_stmt_execute($grouped_stmt);
$grouped_result = mysqli_stmt_get_result($grouped_stmt);
$grouped_notifications = mysqli_fetch_all($grouped_result, MYSQLI_ASSOC);
mysqli_stmt_close($grouped_stmt);

// ========== GET UNREAD COUNT FOR SPECIFIC TYPES ==========
$type_unread_query = "SELECT 
                        notification_type,
                        COUNT(*) as unread_count
                      FROM client_notifications 
                      WHERE client_id = ? AND is_read = 0 AND is_archived = 0
                      GROUP BY notification_type";

$type_unread_stmt = mysqli_prepare($conn, $type_unread_query);
mysqli_stmt_bind_param($type_unread_stmt, "i", $client_id);
mysqli_stmt_execute($type_unread_stmt);
$type_unread_result = mysqli_stmt_get_result($type_unread_stmt);
$type_unread_counts = mysqli_fetch_all($type_unread_result, MYSQLI_ASSOC);
mysqli_stmt_close($type_unread_stmt);

// Convert to associative array
$unread_by_type = [];
foreach ($type_unread_counts as $tuc) {
    $unread_by_type[$tuc['notification_type']] = (int)$tuc['unread_count'];
}

// ========== GET USER PREFERENCES ==========
$pref_query = "SELECT * FROM notification_preferences WHERE client_id = ?";
$pref_stmt = mysqli_prepare($conn, $pref_query);
mysqli_stmt_bind_param($pref_stmt, "i", $client_id);
mysqli_stmt_execute($pref_stmt);
$pref_result = mysqli_stmt_get_result($pref_stmt);
$preferences = mysqli_fetch_assoc($pref_result);
mysqli_stmt_close($pref_stmt);

// ========== FORMAT NOTIFICATIONS ==========
$type_icons = [
    'case_update' => 'fa-briefcase',
    'payment' => 'fa-credit-card',
    'document' => 'fa-file-alt',
    'dispute' => 'fa-gavel',
    'ticket' => 'fa-headset',
    'promotion' => 'fa-tag',
    'score_update' => 'fa-chart-line',
    'system' => 'fa-bell'
];

$type_colors = [
    'case_update' => '#0d9e78',
    'payment' => '#2563eb',
    'document' => '#7c3aed',
    'dispute' => '#dc2626',
    'ticket' => '#d97706',
    'promotion' => '#ec489a',
    'score_update' => '#059669',
    'system' => '#6b7280'
];

$priority_colors = [
    'high' => '#dc2626',
    'medium' => '#d97706',
    'low' => '#6b7280'
];

foreach ($notifications as &$n) {
    $n['icon'] = $n['icon'] ?? ($type_icons[$n['notification_type']] ?? 'fa-bell');
    $n['color'] = $n['color'] ?? ($type_colors[$n['notification_type']] ?? '#6b7280');
    $n['priority_color'] = $priority_colors[$n['priority']] ?? '#6b7280';
    $n['type_label'] = ucfirst(str_replace('_', ' ', $n['notification_type']));
}

// ========== GET TOTAL COUNT FOR PAGINATION ==========
$total_query = "SELECT COUNT(*) as total FROM client_notifications WHERE client_id = ? AND is_archived = 0";
if ($type_filter !== 'all') {
    $total_query .= " AND notification_type = ?";
}
if ($read_filter === 'unread') {
    $total_query .= " AND is_read = 0";
} elseif ($read_filter === 'read') {
    $total_query .= " AND is_read = 1";
}

$total_stmt = mysqli_prepare($conn, $total_query);
$total_params = [$client_id];
$total_types = "i";

if ($type_filter !== 'all') {
    $total_params[] = $type_filter;
    $total_types .= "s";
}

mysqli_stmt_bind_param($total_stmt, $total_types, ...$total_params);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_data = mysqli_fetch_assoc($total_result);
$total_count = $total_data['total'] ?? 0;
mysqli_stmt_close($total_stmt);

// ========== GET RECENT UNREAD FOR QUICK VIEW ==========
$recent_unread_query = "SELECT id, title, message, notification_type, created_at 
                        FROM client_notifications 
                        WHERE client_id = ? AND is_read = 0 AND is_archived = 0
                        ORDER BY priority DESC, created_at DESC LIMIT 5";

$recent_stmt = mysqli_prepare($conn, $recent_unread_query);
mysqli_stmt_bind_param($recent_stmt, "i", $client_id);
mysqli_stmt_execute($recent_stmt);
$recent_result = mysqli_stmt_get_result($recent_stmt);
$recent_unread = mysqli_fetch_all($recent_result, MYSQLI_ASSOC);
mysqli_stmt_close($recent_stmt);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'data' => $notifications,
    'total' => count($notifications),
    'total_all' => (int)$total_count,
    'has_more' => ($offset + $limit) < $total_count,
    'stats' => [
        'total' => (int)($counts['total'] ?? 0),
        'unread_count' => (int)($counts['unread_count'] ?? 0),
        'case_updates' => (int)($counts['case_updates'] ?? 0),
        'payment_notifications' => (int)($counts['payment_notifications'] ?? 0),
        'document_notifications' => (int)($counts['document_notifications'] ?? 0),
        'dispute_notifications' => (int)($counts['dispute_notifications'] ?? 0),
        'ticket_notifications' => (int)($counts['ticket_notifications'] ?? 0),
        'promotions' => (int)($counts['promotions'] ?? 0),
        'high_priority_unread' => (int)($counts['high_priority_unread'] ?? 0)
    ],
    'unread_by_type' => $unread_by_type,
    'grouped_by_date' => $grouped_notifications,
    'recent_unread' => $recent_unread,
    'preferences' => $preferences,
    'filters' => [
        'type' => $type_filter,
        'read' => $read_filter,
        'limit' => $limit,
        'offset' => $offset
    ],
    'pagination' => [
        'current_page' => floor($offset / $limit) + 1,
        'per_page' => $limit,
        'total_pages' => ceil($total_count / $limit),
        'total_records' => (int)$total_count
    ]
]);

mysqli_close($conn);
?>