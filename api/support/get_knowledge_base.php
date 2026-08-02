<?php
// api/support/get_knowledge_base.php
session_start();
header('Content-Type: application/json');

$allowed_roles = ['support_agent', 'support_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Create knowledge_base table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS knowledge_base (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    content TEXT NOT NULL,
    views INT DEFAULT 0,
    helpful_count INT DEFAULT 0,
    not_helpful_count INT DEFAULT 0,
    status ENUM('draft','published') DEFAULT 'published',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (category),
    INDEX (status)
)");

// Get total articles
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM knowledge_base WHERE status = 'published'"))['c'] ?? 0;

// Get total views
$total_views = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(views) as v FROM knowledge_base"))['v'] ?? 0;

// Get helpful rate
$helpful_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(helpful_count) as h, SUM(not_helpful_count) as nh FROM knowledge_base")) ?? ['h' => 0, 'nh' => 0];
$helpful_rate = ($helpful_total['h'] + $helpful_total['nh']) > 0 ? round(($helpful_total['h'] / ($helpful_total['h'] + $helpful_total['nh'])) * 100) : 0;

// Get all articles
$articles = [];
$result = mysqli_query($conn, "SELECT id, title, category, views, helpful_count, not_helpful_count, created_at FROM knowledge_base WHERE status = 'published' ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $articles[] = $row;
}

echo json_encode([
    'success' => true,
    'total_articles' => $total,
    'total_views' => $total_views,
    'helpful_rate' => $helpful_rate,
    'articles' => $articles
]);

mysqli_close($conn);
?>