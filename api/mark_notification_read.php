<?php
// ============================================================
// CIBIL REPAIR CRM - Mark Notification as Read API
// Endpoint: /api/mark_notification_read.php
// Method: POST
// ============================================================

// ============================================================
// CORS & HEADERS
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

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
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['user_role'] ?? '';

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// ============================================================
# GET INPUT
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$notification_id = isset($input['notification_id']) ? intval($input['notification_id']) : 0;
$mark_all = isset($input['mark_all']) ? filter_var($input['mark_all'], FILTER_VALIDATE_BOOLEAN) : false;

// ============================================================
# CREATE TABLE IF NOT EXISTS
// ============================================================

$createTable = "
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

mysqli_query($conn, $createTable);

// ============================================================
# INSERT SAMPLE NOTIFICATIONS IF EMPTY
// ============================================================

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
            list($title, $message, $type) = $s;
            $title = mysqli_real_escape_string($conn, $title);
            $message = mysqli_real_escape_string($conn, $message);
            mysqli_query($conn, "INSERT INTO notifications (user_id, title, message, type) 
                VALUES ($user_id, '$title', '$message', '$type')");
        }
    }
}

// ============================================================
# MARK NOTIFICATION(S) AS READ
// ============================================================

$success = false;
$affected = 0;

if ($mark_all || $notification_id == 0) {
    // Mark all as read
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    $success = $affected >= 0;
    mysqli_stmt_close($stmt);
    
    $message = $affected > 0 ? "All notifications marked as read" : "No unread notifications";
    
} else {
    // Mark specific notification as read
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    $success = $affected > 0;
    mysqli_stmt_close($stmt);
    
    $message = $success ? "Notification marked as read" : "Notification not found or already read";
}

// ============================================================
# GET UPDATED UNREAD COUNT
// ============================================================

$unreadQuery = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
$unreadStmt = mysqli_prepare($conn, $unreadQuery);
mysqli_stmt_bind_param($unreadStmt, "i", $user_id);
mysqli_stmt_execute($unreadStmt);
$unreadResult = mysqli_stmt_get_result($unreadStmt);
$unreadRow = mysqli_fetch_assoc($unreadResult);
$unreadCount = $unreadRow ? intval($unreadRow['unread']) : 0;
mysqli_stmt_close($unreadStmt);

// ============================================================
# RESPONSE
// ============================================================

echo json_encode([
    'success' => $success,
    'message' => $message,
    'data' => [
        'notification_id' => $notification_id,
        'affected_rows' => $affected,
        'unread_count' => $unreadCount,
        'mark_all' => $mark_all
    ]
]);

mysqli_close($conn);
exit;
?>