<?php
// ============================================================
// CIBIL REPAIR CRM - Add Partner API (FIXED)
// ============================================================

// ===== SHOW ERRORS FOR DEBUGGING =====
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===== SET HEADER =====
header('Content-Type: application/json');

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GET INPUT DATA
// ============================================================

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input) {
    $input = $_POST;
}

// ============================================================
// VALIDATE
// ============================================================

if (empty($input['name'])) {
    echo json_encode(['success' => false, 'error' => 'Partner name is required']);
    exit;
}

if (empty($input['phone'])) {
    echo json_encode(['success' => false, 'error' => 'Phone number is required']);
    exit;
}

$phone = preg_replace('/[^0-9]/', '', $input['phone']);
if (strlen($phone) != 10) {
    echo json_encode(['success' => false, 'error' => 'Invalid phone number. Must be 10 digits']);
    exit;
}

// ============================================================
// FIX TABLE STRUCTURE
// ============================================================

// Drop problematic unique key if exists
$keys = mysqli_query($conn, "SHOW INDEX FROM partners WHERE Key_name = 'mobile_unique'");
if (mysqli_num_rows($keys) > 0) {
    mysqli_query($conn, "ALTER TABLE partners DROP INDEX mobile_unique");
}

// Drop mobile column if exists (use phone instead)
$cols = mysqli_query($conn, "SHOW COLUMNS FROM partners LIKE 'mobile'");
if (mysqli_num_rows($cols) > 0) {
    mysqli_query($conn, "ALTER TABLE partners DROP COLUMN mobile");
}

// Make sure phone column exists
$cols = mysqli_query($conn, "SHOW COLUMNS FROM partners LIKE 'phone'");
if (mysqli_num_rows($cols) == 0) {
    mysqli_query($conn, "ALTER TABLE partners ADD COLUMN phone VARCHAR(20)");
}

// ============================================================
// CREATE TABLE IF NOT EXISTS
// ============================================================

$createTable = "CREATE TABLE IF NOT EXISTS partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    owner VARCHAR(255),
    phone VARCHAR(20) UNIQUE,
    email VARCHAR(255),
    commission_rate DECIMAL(5,2) DEFAULT 10.00,
    status VARCHAR(50) DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $createTable);

// ============================================================
// CHECK DUPLICATES
// ============================================================

$phone = mysqli_real_escape_string($conn, $phone);
$name = mysqli_real_escape_string($conn, $input['name']);

$check = mysqli_query($conn, "SELECT id FROM partners WHERE phone = '$phone'");
if ($check && mysqli_num_rows($check) > 0) {
    echo json_encode(['success' => false, 'error' => 'Phone number already exists']);
    exit;
}

// ============================================================
// INSERT PARTNER
// ============================================================

$name = mysqli_real_escape_string($conn, $input['name']);
$location = mysqli_real_escape_string($conn, $input['location'] ?? '');
$owner = mysqli_real_escape_string($conn, $input['owner'] ?? '');
$email = mysqli_real_escape_string($conn, $input['email'] ?? '');
$commission_rate = isset($input['commission_rate']) ? floatval($input['commission_rate']) : 10;
$status = mysqli_real_escape_string($conn, $input['status'] ?? 'active');

// Build notes
$notes = '';
if (isset($input['company_name']) && !empty($input['company_name'])) {
    $notes .= "Company: " . $input['company_name'] . "\n";
}
if (isset($input['address']) && !empty($input['address'])) {
    $notes .= "Address: " . $input['address'] . "\n";
}
$notes = trim(mysqli_real_escape_string($conn, $notes));

$sql = "INSERT INTO partners (name, location, owner, phone, email, commission_rate, status, notes, created_at) 
        VALUES ('$name', '$location', '$owner', '$phone', '$email', $commission_rate, '$status', '$notes', NOW())";

$result = mysqli_query($conn, $sql);

if ($result) {
    $id = mysqli_insert_id($conn);
    
    // Get the inserted partner
    $result2 = mysqli_query($conn, "SELECT * FROM partners WHERE id = $id");
    $partner = mysqli_fetch_assoc($result2);
    
    echo json_encode([
        'success' => true,
        'message' => 'Partner added successfully',
        'data' => $partner
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
exit;
?>