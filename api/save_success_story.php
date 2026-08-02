<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$name = isset($input['name']) ? trim($input['name']) : '';
$city = isset($input['city']) ? trim($input['city']) : '';
$achievement = isset($input['achievement']) ? trim($input['achievement']) : '';
$old_score = isset($input['oldScore']) ? intval($input['oldScore']) : 0;
$new_score = isset($input['newScore']) ? intval($input['newScore']) : 0;
$review = isset($input['review']) ? trim($input['review']) : '';
$rating = isset($input['rating']) ? intval($input['rating']) : 5;

if (empty($name) || empty($review)) {
    echo json_encode(['success' => false, 'error' => 'Name and review required']);
    exit;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS success_stories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    city VARCHAR(100),
    achievement VARCHAR(200),
    old_score INT DEFAULT 0,
    new_score INT DEFAULT 0,
    review TEXT,
    rating INT DEFAULT 5,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$sql = "INSERT INTO success_stories (name, city, achievement, old_score, new_score, review, rating) 
        VALUES ('$name', '$city', '$achievement', $old_score, $new_score, '$review', $rating)";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Story submitted', 'id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>