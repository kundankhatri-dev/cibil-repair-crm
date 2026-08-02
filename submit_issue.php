<?php
// submit_issue.php - Handle issue submission

header('Content-Type: application/json');

// Database configuration
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'your_username';
$password = 'your_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("INSERT INTO client_issues (full_name, phone, email, city, issue_type, problem_description, additional_info, attachments, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $attachments = json_encode($data['attachments'] ?? []);
    
    $stmt->execute([
        $data['fullName'],
        $data['phone'],
        $data['email'],
        $data['city'],
        $data['issueType'],
        $data['problemDescription'],
        $data['additionalInfo'] ?? '',
        $attachments
    ]);
    
    $issueId = $pdo->lastInsertId();
    
    echo json_encode(['success' => true, 'issue_id' => $issueId]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>