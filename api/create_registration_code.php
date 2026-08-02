<?php
// ============================================================
// CIBIL REPAIR CRM - Create Registration Code API (WORKING)
// ============================================================

// ===== SHOW ERRORS FOR DEBUGGING =====
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===== SET HEADER =====
header('Content-Type: application/json');

// ============================================================
// SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// AUTHENTICATION
// ============================================================

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login first.']);
    exit;
}

$loggedInUserId = (int)$_SESSION['user_id'];
$loggedInRole = $_SESSION['user_role'] ?? '';

// ============================================================
// GET INPUT DATA
// ============================================================

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input) {
    $input = $_POST;
}

$createForRole = isset($input['role']) ? trim($input['role']) : '';
$assignedEmail = isset($input['email']) ? trim($input['email']) : '';
$expiryDays = isset($input['expiry_days']) ? intval($input['expiry_days']) : 7;

// ============================================================
// VALIDATION
// ============================================================

if (empty($createForRole)) {
    echo json_encode(['success' => false, 'error' => 'Role is required (partner or client)']);
    exit;
}

if (!in_array($createForRole, ['partner', 'client'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid role. Must be partner or client']);
    exit;
}

if ($loggedInRole !== 'admin' && $createForRole === 'partner') {
    echo json_encode(['success' => false, 'error' => 'Only Admin can create partner accounts']);
    exit;
}

if ($expiryDays < 1 || $expiryDays > 365) {
    $expiryDays = 7;
}

if (!empty($assignedEmail) && !filter_var($assignedEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

// ============================================================
# CHECK/CREATE TABLE
// ============================================================

// First, check if table exists
$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'registration_codes'");
$tableExists = mysqli_num_rows($tableCheck) > 0;

if (!$tableExists) {
    // Create table with simple structure
    $createSql = "CREATE TABLE registration_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        role VARCHAR(50) NOT NULL,
        created_by INT NOT NULL,
        assigned_to_email VARCHAR(255),
        expires_at DATETIME NOT NULL,
        is_used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $createSql);
}

// ============================================================
# GENERATE UNIQUE CODE
// ============================================================

do {
    $random = strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
    $code = 'CIBIL-' . $random;
    
    $check = mysqli_query($conn, "SELECT id FROM registration_codes WHERE code = '$code'");
    $exists = $check && mysqli_num_rows($check) > 0;
} while ($exists);

$expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryDays days"));

// ============================================================
# INSERT CODE
// ============================================================

$sql = "INSERT INTO registration_codes (code, role, created_by, assigned_to_email, expires_at, is_used) 
        VALUES (?, ?, ?, ?, ?, 0)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ssiss', $code, $createForRole, $loggedInUserId, $assignedEmail, $expiresAt);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Registration code created successfully',
        'data' => [
            'code' => $code,
            'role' => $createForRole,
            'expires_at' => $expiresAt,
            'assigned_to_email' => $assignedEmail ?: null,
            'created_by' => $loggedInUserId,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create code: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
exit;
?>