<?php
// Clean any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// Create table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS partner_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$notifications = [];
$result = mysqli_query($conn, "SELECT * FROM partner_notifications WHERE partner_id = $partner_id OR partner_id IS NULL ORDER BY created_at DESC LIMIT 10");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
}

$unread = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM partner_notifications WHERE (partner_id = $partner_id OR partner_id IS NULL) AND is_read = 0");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $unread = (int)($row['cnt'] ?? 0);
}

if (empty($notifications)) {
    mysqli_query($conn, "INSERT INTO partner_notifications (partner_id, title, message, type, created_at) 
                          VALUES ($partner_id, 'Welcome! 🎉', 'Welcome to your partner dashboard. Start adding leads to earn commissions!', 'success', NOW())");
    
    $result = mysqli_query($conn, "SELECT * FROM partner_notifications WHERE partner_id = $partner_id OR partner_id IS NULL ORDER BY created_at DESC LIMIT 10");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = $row;
        }
    }
    $unread = 1;
}

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unread
]);

mysqli_close($conn);
?>