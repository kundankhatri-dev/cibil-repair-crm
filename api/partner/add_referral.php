<?php
// ============================================================
// API: Partner Add Referral
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
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS partner_referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    referral_code VARCHAR(50),
    referred_name VARCHAR(255),
    referred_email VARCHAR(255),
    referred_phone VARCHAR(20),
    type VARCHAR(50) DEFAULT 'partner',
    notes TEXT DEFAULT NULL,
    status ENUM('registered', 'converted', 'inactive') DEFAULT 'registered',
    commission_earned DECIMAL(10,2) DEFAULT 0,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    converted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_partner_id (partner_id),
    INDEX idx_referral_code (referral_code)
)");

// ========== GET INPUT ==========
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$type = trim($data['type'] ?? 'partner');
$notes = trim($data['notes'] ?? '');
$referralCode = trim($data['referral_code'] ?? '');

// ========== VALIDATE ==========
if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Name and email are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

// ========== CHECK DUPLICATE ==========
$checkQuery = "SELECT id FROM partner_referrals WHERE partner_id = $partner_id AND referred_email = '$email'";
$checkResult = mysqli_query($conn, $checkQuery);
if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    echo json_encode(['success' => false, 'error' => 'This email is already added as a referral']);
    mysqli_close($conn);
    exit;
}

// ========== INSERT REFERRAL ==========
$query = "INSERT INTO partner_referrals (partner_id, referral_code, referred_name, referred_email, referred_phone, type, notes, status, registered_at) 
          VALUES ($partner_id, '$referralCode', 
                  '" . mysqli_real_escape_string($conn, $name) . "', 
                  '" . mysqli_real_escape_string($conn, $email) . "', 
                  '" . mysqli_real_escape_string($conn, $phone) . "', 
                  '" . mysqli_real_escape_string($conn, $type) . "', 
                  '" . mysqli_real_escape_string($conn, $notes) . "', 
                  'registered', NOW())";

if (mysqli_query($conn, $query)) {
    $referral_id = mysqli_insert_id($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Referral added successfully',
        'id' => $referral_id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add referral: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>