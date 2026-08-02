<?php
require_once 'config/database.php';
session_start();

header('Content-Type: application/json');

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }
    
    // Get and sanitize input
    $fullname = sanitizeInput($_POST['fullname'] ?? '');
    $phone = trim(preg_replace('/[^0-9]/', '', $_POST['phone'] ?? ''));
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $complaint = sanitizeInput($_POST['complaint'] ?? '');
    $reference_id = sanitizeInput($_POST['reference_id'] ?? '');
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $complaint_id = 'GRV-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Validate
    if (empty($fullname) || empty($phone) || empty($email) || empty($subject) || empty($complaint)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        echo json_encode(['success' => false, 'error' => 'Invalid phone number']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        exit;
    }
    
    // Check rate limit (max 3 complaints per hour per IP)
    if (!checkRateLimit('grievance', 3, 3600)) {
        echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']);
        exit;
    }
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Create table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS grievances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        complaint_id VARCHAR(50) NOT NULL UNIQUE,
        fullname VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(100) NOT NULL,
        complaint TEXT NOT NULL,
        reference_id VARCHAR(100),
        ip_address VARCHAR(45),
        status VARCHAR(20) DEFAULT 'pending',
        admin_notes TEXT,
        resolved_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert into database
    $sql = "INSERT INTO grievances (complaint_id, fullname, phone, email, subject, complaint, reference_id, ip_address, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $result = $db->insert($sql, [
        $complaint_id, $fullname, $phone, $email, $subject, $complaint, $reference_id, $ip_address
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'complaint_id' => $complaint_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
    }
    exit;
}
?>