<?php
// api/support/add_article.php
session_start();
header('Content-Type: application/json');

$allowed_roles = ['support_agent', 'support_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$title = $input['title'] ?? '';
$category = $input['category'] ?? '';
$content = $input['content'] ?? '';

if (empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Title and content are required']);
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

$user_id = $_SESSION['user_id'];

$query = "INSERT INTO knowledge_base (title, category, content, created_by, status) VALUES (?, ?, ?, ?, 'published')";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sssi", $title, $category, $content, $user_id);

if (mysqli_stmt_execute($stmt)) {
    $article_id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Article added successfully',
        'article_id' => $article_id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add article: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>