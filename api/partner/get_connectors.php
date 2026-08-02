<?php
// ============================================================
// API: Get Connectors for Partner
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
    commission_rate DECIMAL(5,2) DEFAULT 15.00,
    leads_referred INT DEFAULT 0,
    commission_earned DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_partner_id (partner_id)
)");

// ========== CHECK AND ADD MISSING COLUMNS ==========
$check_rate = mysqli_query($conn, "SHOW COLUMNS FROM connectors LIKE 'commission_rate'");
if (!$check_rate || mysqli_num_rows($check_rate) == 0) {
    mysqli_query($conn, "ALTER TABLE connectors ADD COLUMN commission_rate DECIMAL(5,2) DEFAULT 15.00");
}

$check_earned = mysqli_query($conn, "SHOW COLUMNS FROM connectors LIKE 'commission_earned'");
if (!$check_earned || mysqli_num_rows($check_earned) == 0) {
    mysqli_query($conn, "ALTER TABLE connectors ADD COLUMN commission_earned DECIMAL(10,2) DEFAULT 0");
}

$check_leads = mysqli_query($conn, "SHOW COLUMNS FROM connectors LIKE 'leads_referred'");
if (!$check_leads || mysqli_num_rows($check_leads) == 0) {
    mysqli_query($conn, "ALTER TABLE connectors ADD COLUMN leads_referred INT DEFAULT 0");
}

// ========== GET CONNECTORS ==========
$query = "SELECT id, name, phone, email, type, company, city, notes, 
                 commission_rate, leads_referred, commission_earned, status, created_at 
          FROM connectors 
          WHERE partner_id = $partner_id AND status = 'active'
          ORDER BY name ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Query failed: ' . mysqli_error($conn)]);
    mysqli_close($conn);
    exit;
}

$connectors = [];
while ($row = mysqli_fetch_assoc($result)) {
    $connectors[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'] ?? '—',
        'phone' => $row['phone'] ?? '—',
        'email' => $row['email'] ?? '—',
        'type' => $row['type'] ?? 'other',
        'company' => $row['company'] ?? '—',
        'city' => $row['city'] ?? '—',
        'notes' => $row['notes'] ?? '',
        'commission_rate' => (float)($row['commission_rate'] ?? 15),
        'leads_referred' => (int)($row['leads_referred'] ?? 0),
        'commission_earned' => (float)($row['commission_earned'] ?? 0),
        'status' => $row['status'] ?? 'active',
        'created_at' => $row['created_at'] ?? ''
    ];
}

echo json_encode([
    'success' => true,
    'connectors' => $connectors,
    'total' => count($connectors)
]);

mysqli_close($conn);
?>