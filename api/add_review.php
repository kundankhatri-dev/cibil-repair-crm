<?php
header('Content-Type: application/json');
session_start();

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$reviewText = isset($input['review_text']) ? trim($input['review_text']) : '';
$rating = isset($input['rating']) ? (int)$input['rating'] : 5;

// Validate
if (!$name || !$reviewText) {
    echo json_encode(['success' => false, 'error' => 'Name and review text are required']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Check if table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'reviews'");
if ($stmt->rowCount() === 0) {
    $pdo->exec("CREATE TABLE reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        review_text TEXT NOT NULL,
        rating INT DEFAULT 5,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
}

// Insert review
$stmt = $pdo->prepare("INSERT INTO reviews (name, email, review_text, rating) VALUES (?, ?, ?, ?)");
$stmt->execute([$name, $email, $reviewText, $rating]);
$id = $pdo->lastInsertId();

echo json_encode([
    'success' => true,
    'message' => 'Review submitted successfully!',
    'data' => [
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'review_text' => $reviewText,
        'rating' => $rating,
        'created_at' => date('Y-m-d H:i:s'),
        'formatted_date' => date('d M Y'),
        'stars' => str_repeat('⭐', $rating)
    ]
]);
exit;
?>