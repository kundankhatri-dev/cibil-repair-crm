<?php
// ============================================================
// ADD NOTIFICATION API - FULL WORKING VERSION
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

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

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$title = isset($input['title']) ? trim($input['title']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$type = isset($input['type']) ? trim($input['type']) : 'info';

if (empty($title) || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Title and message are required']);
    exit;
}

// Create table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    type VARCHAR(20) DEFAULT 'info',
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Insert notification
$sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'isss', $user_id, $title, $message, $type);
mysqli_stmt_execute($stmt);
$id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// Response
if ($id > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Notification created successfully',
        'id' => $id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to create notification']);
}

mysqli_close($conn);
?>