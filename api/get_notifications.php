<?php
// ============================================================
// CIBIL REPAIR CRM - Get Notifications API
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
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

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Create table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    type VARCHAR(20) DEFAULT 'info',
    is_read TINYINT DEFAULT 0,
    link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

mysqli_query($conn, $create_table);

// Insert sample data if empty
$check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $user_id");
if ($check) {
    $row = mysqli_fetch_assoc($check);
    if ($row['cnt'] == 0) {
        $samples = [
            ['Welcome to Dashboard', 'Welcome to your admin dashboard! Explore all features.', 'success'],
            ['New Features Added', 'Notification system is now available.', 'info'],
            ['Security Tip', 'Regular backups are recommended.', 'warning']
        ];
        foreach ($samples as $s) {
            $title = mysqli_real_escape_string($conn, $s[0]);
            $message = mysqli_real_escape_string($conn, $s[1]);
            $type = $s[2];
            mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type) 
                VALUES ($user_id, '$title', '$message', '$type')");
        }
    }
}

// Mark as read if requested
if (isset($_GET['mark_read']) && $_GET['mark_read'] > 0) {
    $id = intval($_GET['mark_read']);
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE id = $id AND user_id = $user_id");
}

// Mark all as read if requested
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] == 1) {
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
}

// Get parameters
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// Get notifications
$sql = "SELECT id, title, message, type, is_read, 
        DATE_FORMAT(created_at, '%d %b %Y, %h:%i %p') as formatted_date,
        created_at 
        FROM notifications 
        WHERE user_id = $user_id 
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $sql);
$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}

// Get unread count
$unread_result = mysqli_query($conn, "SELECT COUNT(*) as unread FROM notifications WHERE user_id = $user_id AND is_read = 0");
$unread_row = mysqli_fetch_assoc($unread_result);
$unread_count = intval($unread_row['unread'] ?? 0);

// Get total count
$total_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM notifications WHERE user_id = $user_id");
$total_row = mysqli_fetch_assoc($total_result);
$total_count = intval($total_row['total'] ?? 0);

// Response
echo json_encode([
    'success' => true,
    'data' => [
        'notifications' => $notifications,
        'unread_count' => $unread_count,
        'total_count' => $total_count,
        'limit' => $limit,
        'offset' => $offset,
        'has_more' => ($offset + $limit) < $total_count
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>