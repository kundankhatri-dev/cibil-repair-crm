<?php
// api/partner/save_bank.php
// Partner Save Bank API - Save/Update bank details for payouts

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role, name, email FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);

$bank_name = trim($data['bank_name'] ?? '');
$account_number = trim($data['account_number'] ?? '');
$ifsc_code = strtoupper(trim($data['ifsc_code'] ?? ''));
$account_holder = trim($data['account_holder'] ?? '');

// ========== VALIDATE REQUIRED FIELDS ==========
$errors = [];

if (empty($bank_name)) {
    $errors[] = 'Bank name is required';
}

if (empty($account_number)) {
    $errors[] = 'Account number is required';
}

if (empty($ifsc_code)) {
    $errors[] = 'IFSC code is required';
}

if (empty($account_holder)) {
    $errors[] = 'Account holder name is required';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
    exit;
}

// ========== VALIDATE BANK NAME ==========
if (strlen($bank_name) < 2) {
    echo json_encode(['success' => false, 'error' => 'Bank name must be at least 2 characters']);
    exit;
}

if (strlen($bank_name) > 100) {
    echo json_encode(['success' => false, 'error' => 'Bank name is too long (maximum 100 characters)']);
    exit;
}

if (!preg_match('/^[a-zA-Z\s\-\.]+$/', $bank_name)) {
    echo json_encode(['success' => false, 'error' => 'Bank name should contain only letters, spaces, hyphens, and dots']);
    exit;
}

// ========== VALIDATE ACCOUNT NUMBER ==========
$account_length = strlen($account_number);
if ($account_length < 9 || $account_length > 18) {
    echo json_encode(['success' => false, 'error' => 'Account number should be 9-18 digits']);
    exit;
}

if (!preg_match('/^[0-9]+$/', $account_number)) {
    echo json_encode(['success' => false, 'error' => 'Account number should contain only digits']);
    exit;
}

// ========== VALIDATE IFSC CODE ==========
if (strlen($ifsc_code) !== 11) {
    echo json_encode(['success' => false, 'error' => 'IFSC code must be exactly 11 characters']);
    exit;
}

// IFSC format: First 4 letters, 5th is 0, last 6 alphanumeric
if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc_code)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid IFSC code format. Format: First 4 letters, then 0, then 6 alphanumeric (e.g., SBIN0001234)'
    ]);
    exit;
}

// ========== VALIDATE ACCOUNT HOLDER NAME ==========
if (strlen($account_holder) < 3) {
    echo json_encode(['success' => false, 'error' => 'Account holder name must be at least 3 characters']);
    exit;
}

if (strlen($account_holder) > 100) {
    echo json_encode(['success' => false, 'error' => 'Account holder name is too long (maximum 100 characters)']);
    exit;
}

if (!preg_match('/^[a-zA-Z\s\.]+$/', $account_holder)) {
    echo json_encode(['success' => false, 'error' => 'Account holder name should contain only letters, spaces, and dots']);
    exit;
}

// ========== CHECK IF PARTNERS TABLE EXISTS ==========
$checkPartnersTable = mysqli_query($conn, "SHOW TABLES LIKE 'partners'");
if (mysqli_num_rows($checkPartnersTable) == 0) {
    // Create partners table if not exists
    $createTable = "CREATE TABLE IF NOT EXISTS partners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        bank_name VARCHAR(100),
        account_number VARCHAR(20),
        ifsc_code VARCHAR(20),
        account_holder VARCHAR(100),
        company_name VARCHAR(255),
        total_leads INT DEFAULT 0,
        total_commission DECIMAL(12,2) DEFAULT 0,
        pending_payout DECIMAL(12,2) DEFAULT 0,
        commission_rate DECIMAL(5,2) DEFAULT 10.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    )";
    mysqli_query($conn, $createTable);
}

// ========== CHECK IF PARTNER EXISTS IN PARTNERS TABLE ==========
$partner_exists = false;
$current_bank_data = null;

$check_stmt = mysqli_prepare($conn, "SELECT id, bank_name, account_number, ifsc_code, account_holder FROM partners WHERE user_id = ?");
if (!$check_stmt) {
    // Try with id column (if user_id doesn't exist)
    $check_stmt = mysqli_prepare($conn, "SELECT id, bank_name, account_number, ifsc_code, account_holder FROM partners WHERE id = ?");
}

if ($check_stmt) {
    mysqli_stmt_bind_param($check_stmt, "i", $partner_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $current_bank_data = mysqli_fetch_assoc($check_result);
    $partner_exists = ($current_bank_data !== null);
    mysqli_stmt_close($check_stmt);
}

// ========== CHECK IF BANK DETAILS ARE CHANGING ==========
$is_updating = false;
if ($partner_exists && $current_bank_data) {
    if ($current_bank_data['bank_name'] !== $bank_name ||
        $current_bank_data['account_number'] !== $account_number ||
        $current_bank_data['ifsc_code'] !== $ifsc_code ||
        $current_bank_data['account_holder'] !== $account_holder) {
        $is_updating = true;
    }
} else {
    $is_updating = true;
}

if (!$is_updating) {
    echo json_encode([
        'success' => true,
        'message' => 'Bank details are already up to date',
        'is_new' => false,
        'bank_details' => [
            'bank_name' => $bank_name,
            'account_number_masked' => maskAccountNumber($account_number),
            'ifsc_code' => $ifsc_code,
            'account_holder' => $account_holder
        ]
    ]);
    exit;
}

// ========== SAVE BANK DETAILS ==========
if ($partner_exists) {
    // Update existing partner record
    $query = "UPDATE partners SET bank_name = ?, account_number = ?, ifsc_code = ?, account_holder = ?, updated_at = NOW() WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        // Try with id column
        $query = "UPDATE partners SET bank_name = ?, account_number = ?, ifsc_code = ?, account_holder = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
    }
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssi", $bank_name, $account_number, $ifsc_code, $account_holder, $partner_id);
    }
} else {
    // Insert new partner record
    $query = "INSERT INTO partners (user_id, bank_name, account_number, ifsc_code, account_holder, commission_rate) VALUES (?, ?, ?, ?, ?, 10.00)";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "issss", $partner_id, $bank_name, $account_number, $ifsc_code, $account_holder);
    }
}

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_stmt_execute($stmt)) {
    // Mask account number for security
    $masked_account = maskAccountNumber($account_number);
    
    // Log activity
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'save_bank', ?, NOW())");
        if ($log_stmt) {
            $action = $partner_exists ? 'updated' : 'added';
            $description = "$action bank details for payouts: $bank_name (Account: $masked_account)";
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => $partner_exists ? 'Bank details updated successfully' : 'Bank details saved successfully',
        'is_new' => !$partner_exists,
        'bank_details' => [
            'bank_name' => $bank_name,
            'account_number_masked' => $masked_account,
            'ifsc_code' => $ifsc_code,
            'account_holder' => $account_holder
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
}

// ========== HELPER FUNCTION ==========
function maskAccountNumber($account_number) {
    $length = strlen($account_number);
    if ($length <= 4) {
        return str_repeat('*', $length);
    }
    $visible = 4;
    $masked_length = $length - $visible;
    return str_repeat('*', $masked_length) . substr($account_number, -$visible);
}

// ========== CLEAN UP ==========
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($role_check)) mysqli_stmt_close($role_check);

mysqli_close($conn);
?>