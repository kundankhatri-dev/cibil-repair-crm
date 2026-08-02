<?php
// ============================================================
// API: Partner Add Connector
// ============================================================

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

// ========== CREATE TABLE IF NOT EXISTS ==========
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS connectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    type VARCHAR(50) DEFAULT 'other',
    company VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    leads_referred INT DEFAULT 0,
    commission_due DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_partner_id (partner_id)
)");

// ========== GET INPUT ==========
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$email = trim($data['email'] ?? '');
$type = trim($data['type'] ?? 'other');
$company = trim($data['company'] ?? '');
$city = trim($data['city'] ?? '');
$notes = trim($data['notes'] ?? '');

// ========== VALIDATE ==========
if (empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Name and phone are required']);
    exit;
}

// ========== INSERT ==========
$query = "INSERT INTO connectors (partner_id, name, phone, email, type, company, city, notes, created_at) 
          VALUES ($partner_id, '" . mysqli_real_escape_string($conn, $name) . "', 
                  '" . mysqli_real_escape_string($conn, $phone) . "', 
                  '" . mysqli_real_escape_string($conn, $email) . "', 
                  '" . mysqli_real_escape_string($conn, $type) . "', 
                  '" . mysqli_real_escape_string($conn, $company) . "', 
                  '" . mysqli_real_escape_string($conn, $city) . "', 
                  '" . mysqli_real_escape_string($conn, $notes) . "', NOW())";

if (mysqli_query($conn, $query)) {
    $connector_id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Connector added successfully',
        'id' => $connector_id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add connector: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>