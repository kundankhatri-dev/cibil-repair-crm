<?php
// ============================================================
// CIBIL REPAIR CRM - Add Entity API
// ============================================================

// ===== SHOW ERRORS FOR DEBUGGING =====
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===== SET HEADER =====
header('Content-Type: application/json');

// ===== TEST MODE =====
$isTestMode = isset($_GET['test']) && $_GET['test'] === 'true';

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'u929623538_cibil';
$db_user = getenv('DB_USER') ?: 'u929623538_cibilrepair';
$db_pass = getenv('DB_PASS') ?: 'Kundanlaxmi@1995';

$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . mysqli_connect_error()
    ]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// ============================================================
// ENTITY TYPES
// ============================================================

$entityTypes = [
    'bank' => 'Bank',
    'lawyer' => 'Law Firm / Advocate',
    'ca' => 'Chartered Accountant',
    'franchise' => 'Franchise Store',
    'real_estate' => 'Real Estate Agent',
    'insurance' => 'Insurance Agent',
    'consultant' => 'Business Consultant',
    'agency' => 'Recruitment Agency',
    'broker' => 'Broker / Agent',
    'other' => 'Other'
];

// ============================================================
// EXTRACT DATA
// ============================================================

$name = isset($input['name']) ? trim($input['name']) : '';
$contact = isset($input['contact']) ? trim($input['contact']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$status = isset($input['status']) ? trim($input['status']) : 'active';
$entity_type = isset($input['entity_type']) ? trim($input['entity_type']) : 'bank';
$entity_type_label = isset($entityTypes[$entity_type]) ? $entityTypes[$entity_type] : 'Other';

// ============================================================
// VALIDATION
// ============================================================

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Name is required']);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode(['success' => false, 'error' => 'Invalid phone number. Must be 10 digits']);
    exit;
}

// ============================================================
// CHECK DUPLICATES
// ============================================================

$check = mysqli_query($conn, "SELECT id FROM banks WHERE name = '" . mysqli_real_escape_string($conn, $name) . "'");
if ($check && mysqli_num_rows($check) > 0) {
    echo json_encode(['success' => false, 'error' => 'Name already exists']);
    exit;
}

if (!empty($email)) {
    $check = mysqli_query($conn, "SELECT id FROM banks WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'");
    if ($check && mysqli_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'error' => 'Email already exists']);
        exit;
    }
}

if (!empty($phone)) {
    $check = mysqli_query($conn, "SELECT id FROM banks WHERE phone = '" . mysqli_real_escape_string($conn, $phone) . "'");
    if ($check && mysqli_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'error' => 'Phone number already exists']);
        exit;
    }
}

// ============================================================
// CHECK IF BANKS TABLE EXISTS AND HAS CORRECT COLUMNS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'banks'");
if (mysqli_num_rows($tableCheck) == 0) {
    // Create table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS banks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        contact VARCHAR(255),
        email VARCHAR(255),
        phone VARCHAR(20),
        status VARCHAR(50) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $createTable);
} else {
    // Check if notes column exists, if not add it
    $columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM banks LIKE 'notes'");
    if (mysqli_num_rows($columnCheck) == 0) {
        mysqli_query($conn, "ALTER TABLE banks ADD COLUMN notes TEXT AFTER status");
    }
}

// ============================================================
// INSERT
// ============================================================

$sql = "INSERT INTO banks (name, contact, email, phone, status, notes, created_at) 
        VALUES (
            '" . mysqli_real_escape_string($conn, $name) . "',
            '" . mysqli_real_escape_string($conn, $contact) . "',
            '" . mysqli_real_escape_string($conn, $email) . "',
            '" . mysqli_real_escape_string($conn, $phone) . "',
            '" . mysqli_real_escape_string($conn, $status) . "',
            '" . mysqli_real_escape_string($conn, $entity_type_label) . "',
            NOW()
        )";

if (mysqli_query($conn, $sql)) {
    $id = mysqli_insert_id($conn);
    
    echo json_encode([
        'success' => true,
        'message' => $entity_type_label . ' added successfully',
        'data' => [
            'id' => $id,
            'name' => $name,
            'entity_type' => $entity_type,
            'entity_type_label' => $entity_type_label,
            'contact' => $contact,
            'email' => $email,
            'phone' => $phone,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s')
        ]
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