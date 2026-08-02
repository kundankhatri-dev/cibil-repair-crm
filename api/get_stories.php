<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Check if table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'success_stories'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    // Create table
    $createTable = "
        CREATE TABLE success_stories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            city VARCHAR(100),
            achievement VARCHAR(200),
            old_score INT,
            new_score INT,
            review TEXT NOT NULL,
            rating INT DEFAULT 5,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    mysqli_query($conn, $createTable);
    
    // Insert sample data
    $samples = [
        ['Maneet Singh', 'Delhi', 'Home Loan Approved', 620, 745, 'My CIBIL score went from 620 to 745 in just 45 days.', 5],
        ['Priya Sharma', 'Mumbai', 'Credit Card Approved', 650, 730, 'Professional and transparent service.', 5],
        ['Rajesh Kumar', 'Bangalore', 'Business Loan Approved', 580, 710, 'I had multiple wrong entries. The experts removed all of them.', 5]
    ];
    
    foreach ($samples as $s) {
        $sql = "INSERT INTO success_stories (name, city, achievement, old_score, new_score, review, rating, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sssiisi', $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Get approved stories
$sql = "SELECT * FROM success_stories WHERE status = 'approved' ORDER BY created_at DESC LIMIT 50";
$result = mysqli_query($conn, $sql);

$stories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $stories[] = [
        'id' => intval($row['id']),
        'name' => $row['name'],
        'city' => $row['city'] ?? '',
        'achievement' => $row['achievement'] ?? '',
        'old_score' => intval($row['old_score'] ?? 0),
        'new_score' => intval($row['new_score'] ?? 0),
        'review' => $row['review'],
        'rating' => intval($row['rating'] ?? 5),
        'date' => date('d M Y', strtotime($row['created_at']))
    ];
}

echo json_encode([
    'success' => true,
    'stories' => $stories
]);

mysqli_close($conn);
?>