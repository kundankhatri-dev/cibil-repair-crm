<?php
// ============================================================
// CIBIL REPAIR CRM - Delete Success Story API
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADERS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login first.']);
    exit;
}

$loggedInUserId = (int)$_SESSION['user_id'];
$loggedInRole = $_SESSION['user_role'] ?? '';

// Check if user has admin role
if (!in_array($loggedInRole, ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required.']);
    exit;
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
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GET INPUT DATA
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST method allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$storyId = isset($input['story_id']) ? intval($input['story_id']) : 0;

// ============================================================
// VALIDATION
// ============================================================

if ($storyId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid story ID']);
    exit;
}

// ============================================================
# CHECK IF SUCCESS_STORIES TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'success_stories'");
if (mysqli_num_rows($tableCheck) == 0) {
    // Create the table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS success_stories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        city VARCHAR(100),
        achievement TEXT,
        old_score INT,
        new_score INT,
        review TEXT,
        rating DECIMAL(2,1) DEFAULT 0,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $createTable);
}

// ============================================================
# CHECK IF STORY EXISTS
// ============================================================

$selectSql = "SELECT id, name, city, achievement, old_score, new_score, review, rating, status, created_at 
              FROM success_stories 
              WHERE id = ?";
$selectStmt = mysqli_prepare($conn, $selectSql);
mysqli_stmt_bind_param($selectStmt, 'i', $storyId);
mysqli_stmt_execute($selectStmt);
$selectResult = mysqli_stmt_get_result($selectStmt);
$deletedStory = mysqli_fetch_assoc($selectResult);
mysqli_stmt_close($selectStmt);

if (!$deletedStory) {
    echo json_encode(['success' => false, 'error' => 'Story not found']);
    exit;
}

// ============================================================
# DELETE THE STORY
// ============================================================

$deleteSql = "DELETE FROM success_stories WHERE id = ?";
$deleteStmt = mysqli_prepare($conn, $deleteSql);
mysqli_stmt_bind_param($deleteStmt, 'i', $storyId);

if (mysqli_stmt_execute($deleteStmt)) {
    // Log activity
    $user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $logDetails = "Deleted success story: {$deletedStory['name']} (ID: $storyId)";
    
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_name VARCHAR(100),
        action VARCHAR(100),
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    mysqli_query($conn, "INSERT INTO activity_logs (user_id, user_name, action, details, ip_address, created_at) 
                         VALUES ($loggedInUserId, '$user_name', 'Deleted success story', '$logDetails', '$ip', NOW())");
    
    echo json_encode([
        'success' => true,
        'message' => 'Story deleted successfully',
        'data' => [
            'story_id' => $storyId,
            'name' => $deletedStory['name'],
            'city' => $deletedStory['city'],
            'status' => $deletedStory['status']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete story: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($deleteStmt);
mysqli_close($conn);
exit;
?>