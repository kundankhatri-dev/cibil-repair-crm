<?php
// api/partner/update_commission_rate.php
// Partner Update Commission Rate API - Update commission rate (Admin only)

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
$role_check = mysqli_prepare($conn, "SELECT role, name FROM users WHERE id = ?");
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

// ========== CRITICAL: Only Admin should update commission rates ==========
// Check if user has admin privileges
$is_admin = ($role_data['role'] === 'admin');

if (!$is_admin) {
    echo json_encode([
        'success' => false, 
        'error' => 'Permission denied. Only administrators can update commission rates.',
        'current_rate' => null
    ]);
    exit;
}

// ========== ENSURE PARTNERS TABLE EXISTS ==========
$partnersTable = 'partners';
$checkPartnersTable = mysqli_query($conn, "SHOW TABLES LIKE '$partnersTable'");
if (mysqli_num_rows($checkPartnersTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS $partnersTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        company_name VARCHAR(255),
        bank_name VARCHAR(100),
        account_number VARCHAR(20),
        ifsc_code VARCHAR(20),
        account_holder VARCHAR(100),
        commission_rate DECIMAL(5,2) DEFAULT 10.00,
        total_leads INT DEFAULT 0,
        total_converted INT DEFAULT 0,
        total_commission DECIMAL(12,2) DEFAULT 0,
        pending_payout DECIMAL(12,2) DEFAULT 0,
        referral_code VARCHAR(50) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createTable);
}

// Create rate history table
$historyTable = 'partner_commission_history';
$checkHistoryTable = mysqli_query($conn, "SHOW TABLES LIKE '$historyTable'");
if (mysqli_num_rows($checkHistoryTable) == 0) {
    $createHistory = "CREATE TABLE IF NOT EXISTS $historyTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        old_rate DECIMAL(5,2),
        new_rate DECIMAL(5,2),
        changed_by INT,
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createHistory);
}

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);
$target_partner_id = isset($data['partner_id']) ? (int)$data['partner_id'] : $partner_id;
$commission_rate = isset($data['commission_rate']) ? (float)$data['commission_rate'] : 0;
$reason = isset($data['reason']) ? trim($data['reason']) : '';

// ========== VALIDATE INPUT ==========
if ($commission_rate <= 0) {
    echo json_encode(['success' => false, 'error' => 'Commission rate must be greater than 0']);
    exit;
}

if ($commission_rate < 5) {
    echo json_encode(['success' => false, 'error' => 'Minimum commission rate is 5%']);
    exit;
}

if ($commission_rate > 30) {
    echo json_encode(['success' => false, 'error' => 'Maximum commission rate is 30%']);
    exit;
}

// Validate target partner exists
$checkTarget = mysqli_prepare($conn, "SELECT id, name, role FROM users WHERE id = ?");
mysqli_stmt_bind_param($checkTarget, "i", $target_partner_id);
mysqli_stmt_execute($checkTarget);
$targetResult = mysqli_stmt_get_result($checkTarget);
$targetUser = mysqli_fetch_assoc($targetResult);
mysqli_stmt_close($checkTarget);

if (!$targetUser || $targetUser['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Target partner not found']);
    exit;
}

// ========== GET CURRENT RATE ==========
$current_rate = 10.00; // Default
$rate_query = "SELECT commission_rate FROM $partnersTable WHERE user_id = ?";
$rate_stmt = mysqli_prepare($conn, $rate_query);
mysqli_stmt_bind_param($rate_stmt, "i", $target_partner_id);
mysqli_stmt_execute($rate_stmt);
$rate_result = mysqli_stmt_get_result($rate_stmt);
$rate_data = mysqli_fetch_assoc($rate_result);
if ($rate_data) {
    $current_rate = (float)$rate_data['commission_rate'];
}
mysqli_stmt_close($rate_stmt);

// If rate is same, no update needed
if ($current_rate == $commission_rate) {
    echo json_encode([
        'success' => true,
        'message' => 'Commission rate is already set to ' . $commission_rate . '%',
        'commission_rate' => $commission_rate,
        'no_change' => true
    ]);
    exit;
}

// ========== CHECK IF PARTNER EXISTS IN PARTNERS TABLE ==========
$check_stmt = mysqli_prepare($conn, "SELECT id, user_id FROM $partnersTable WHERE user_id = ?");
mysqli_stmt_bind_param($check_stmt, "i", $target_partner_id);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);
$partner_exists = mysqli_stmt_num_rows($check_stmt) > 0;
mysqli_stmt_close($check_stmt);

// ========== START TRANSACTION ==========
mysqli_begin_transaction($conn);

$success = false;

if ($partner_exists) {
    // Update existing partner
    $update_stmt = mysqli_prepare($conn, "UPDATE $partnersTable SET commission_rate = ?, updated_at = NOW() WHERE user_id = ?");
    mysqli_stmt_bind_param($update_stmt, "di", $commission_rate, $target_partner_id);
    if (mysqli_stmt_execute($update_stmt)) {
        $success = true;
    }
    mysqli_stmt_close($update_stmt);
} else {
    // Create new partner record
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO $partnersTable (user_id, commission_rate, created_at) VALUES (?, ?, NOW())");
    mysqli_stmt_bind_param($insert_stmt, "id", $target_partner_id, $commission_rate);
    if (mysqli_stmt_execute($insert_stmt)) {
        $success = true;
    }
    mysqli_stmt_close($insert_stmt);
}

if ($success) {
    // Log rate change history
    $history_stmt = mysqli_prepare($conn, "INSERT INTO $historyTable (partner_id, old_rate, new_rate, changed_by, reason, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    mysqli_stmt_bind_param($history_stmt, "iddis", $target_partner_id, $current_rate, $commission_rate, $partner_id, $reason);
    mysqli_stmt_execute($history_stmt);
    mysqli_stmt_close($history_stmt);
    
    // Log activity
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'update_commission', ?, NOW())");
        if ($log_stmt) {
            $description = "Updated commission rate for " . $targetUser['name'] . " from {$current_rate}% to {$commission_rate}%";
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Commission rate updated successfully',
        'partner_id' => $target_partner_id,
        'partner_name' => $targetUser['name'],
        'old_rate' => $current_rate,
        'new_rate' => $commission_rate,
        'changed_by' => $role_data['name']
    ]);
} else {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => 'Failed to update commission rate: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>