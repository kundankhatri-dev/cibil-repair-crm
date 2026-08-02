<?php
// ============================================================
// CIBIL REPAIR CRM - Create Registration Code API (FIXED)
// ============================================================

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

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$role = isset($input['role']) ? trim($input['role']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$expiryDays = isset($input['expiry_days']) ? intval($input['expiry_days']) : 30;

// ============================================================
// VALIDATION
// ============================================================

if (empty($role)) {
    echo json_encode(['success' => false, 'error' => 'Role is required (partner or client)']);
    exit;
}

if (!in_array($role, ['partner', 'client'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid role. Must be partner or client']);
    exit;
}

if ($loggedInRole !== 'admin' && $role === 'partner') {
    echo json_encode(['success' => false, 'error' => 'Only Admin can create partner accounts']);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

// ============================================================
# CREATE TABLE
// ============================================================

$createTable = "CREATE TABLE IF NOT EXISTS registration_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    role VARCHAR(50) NOT NULL,
    created_by INT NOT NULL,
    assigned_to_email VARCHAR(255),
    expires_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $createTable);

// ============================================================
# GENERATE CODE
// ============================================================

$prefix = 'CIBIL';
do {
    $random = strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
    $code = $prefix . '-' . $random;
    
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
mysqli_stmt_bind_param($stmt, 'ssiss', $code, $role, $loggedInUserId, $email, $expiresAt);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Registration code created successfully',
        'data' => [
            'code' => $code,
            'role' => $role,
            'expires_at' => $expiresAt,
            'assigned_to_email' => $email ?: null
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