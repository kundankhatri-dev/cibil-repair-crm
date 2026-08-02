<?php
// ============================================================
// API: Get Referrals for Partner
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
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
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    type VARCHAR(50) DEFAULT 'partner',
    commission_rate DECIMAL(5,2) DEFAULT 10.00,
    leads_referred INT DEFAULT 0,
    earnings DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_partner_id (partner_id)
)");

// ========== CHECK AND ADD MISSING COLUMNS ==========
$check_rate = mysqli_query($conn, "SHOW COLUMNS FROM referrals LIKE 'commission_rate'");
if (!$check_rate || mysqli_num_rows($check_rate) == 0) {
    mysqli_query($conn, "ALTER TABLE referrals ADD COLUMN commission_rate DECIMAL(5,2) DEFAULT 10.00");
}

$check_earnings = mysqli_query($conn, "SHOW COLUMNS FROM referrals LIKE 'earnings'");
if (!$check_earnings || mysqli_num_rows($check_earnings) == 0) {
    mysqli_query($conn, "ALTER TABLE referrals ADD COLUMN earnings DECIMAL(10,2) DEFAULT 0");
}

$check_leads = mysqli_query($conn, "SHOW COLUMNS FROM referrals LIKE 'leads_referred'");
if (!$check_leads || mysqli_num_rows($check_leads) == 0) {
    mysqli_query($conn, "ALTER TABLE referrals ADD COLUMN leads_referred INT DEFAULT 0");
}

$check_notes = mysqli_query($conn, "SHOW COLUMNS FROM referrals LIKE 'notes'");
if (!$check_notes || mysqli_num_rows($check_notes) == 0) {
    mysqli_query($conn, "ALTER TABLE referrals ADD COLUMN notes TEXT DEFAULT NULL");
}

// ========== GET REFERRALS ==========
$query = "SELECT id, name, email, phone, type, commission_rate, 
                 leads_referred, earnings, status, notes, created_at 
          FROM referrals 
          WHERE partner_id = $partner_id AND status = 'active'
          ORDER BY name ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Query failed: ' . mysqli_error($conn)]);
    mysqli_close($conn);
    exit;
}

$referrals = [];
while ($row = mysqli_fetch_assoc($result)) {
    $referrals[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'] ?? '—',
        'email' => $row['email'] ?? '—',
        'phone' => $row['phone'] ?? '—',
        'type' => $row['type'] ?? 'partner',
        'commission_rate' => (float)($row['commission_rate'] ?? 10),
        'leads_referred' => (int)($row['leads_referred'] ?? 0),
        'earnings' => (float)($row['earnings'] ?? 0),
        'status' => $row['status'] ?? 'active',
        'notes' => $row['notes'] ?? '',
        'created_at' => $row['created_at'] ?? ''
    ];
}

echo json_encode([
    'success' => true,
    'referrals' => $referrals,
    'total' => count($referrals)
]);

mysqli_close($conn);
?>