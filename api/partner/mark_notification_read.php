<?php
// api/partner/mark_notification_read.php
// Partner Mark Notification Read API - Mark notifications as read

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
$role_check = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
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

// ========== ENSURE NOTIFICATIONS TABLE EXISTS ==========
$notificationsTable = 'partner_notifications';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$notificationsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS $notificationsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        read_at DATETIME DEFAULT NULL,
        link VARCHAR(500) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createTable);
}

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($data['notification_id']) ? (int)$data['notification_id'] : 0;

// ========== MARK NOTIFICATION(S) AS READ ==========
if ($notification_id === 0) {
    // Mark all notifications as read for this partner
    $query = "UPDATE $notificationsTable SET is_read = 1, read_at = NOW() WHERE partner_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $partner_id);
    $action = 'all';
} else {
    // Mark specific notification as read
    // First verify notification belongs to this partner
    $check_stmt = mysqli_prepare($conn, "SELECT id FROM $notificationsTable WHERE id = ? AND partner_id = ?");
    mysqli_stmt_bind_param($check_stmt, "ii", $notification_id, $partner_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) == 0) {
        echo json_encode(['success' => false, 'error' => 'Notification not found or access denied']);
        exit;
    }
    mysqli_stmt_close($check_stmt);
    
    $query = "UPDATE $notificationsTable SET is_read = 1, read_at = NOW() WHERE id = ? AND partner_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $partner_id);
    $action = 'single';
}

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_stmt_execute($stmt)) {
    // Get updated unread count
    $count_query = "SELECT COUNT(*) as unread FROM $notificationsTable WHERE partner_id = ? AND is_read = 0";
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, "i", $partner_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $unread_data = mysqli_fetch_assoc($count_result);
    $unread_count = $unread_data['unread'] ?? 0;
    mysqli_stmt_close($count_stmt);
    
    // Get the marked notification details if single
    $notification = null;
    if ($action === 'single' && $notification_id > 0) {
        $notif_query = "SELECT id, title, message, type, read_at FROM $notificationsTable WHERE id = ?";
        $notif_stmt = mysqli_prepare($conn, $notif_query);
        mysqli_stmt_bind_param($notif_stmt, "i", $notification_id);
        mysqli_stmt_execute($notif_stmt);
        $notif_result = mysqli_stmt_get_result($notif_stmt);
        $notification = mysqli_fetch_assoc($notif_result);
        mysqli_stmt_close($notif_stmt);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $action === 'all' ? 'All notifications marked as read' : 'Notification marked as read',
        'unread_count' => $unread_count,
        'has_unread' => $unread_count > 0,
        'notification' => $notification
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>