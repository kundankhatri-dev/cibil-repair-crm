<?php
// api/partner/change_password.php
// Partner Change Password API - Update partner account password

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

// Verify user is actually a partner and get current data
$role_check = mysqli_prepare($conn, "SELECT id, role, name, email FROM users WHERE id = ?");
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
$current_password = $data['current_password'] ?? '';
$new_password = $data['new_password'] ?? '';
$confirm_password = $data['confirm_password'] ?? '';

// ========== VALIDATE INPUT ==========
// Check if passwords are provided
if (empty($current_password)) {
    echo json_encode(['success' => false, 'error' => 'Current password is required']);
    exit;
}

if (empty($new_password)) {
    echo json_encode(['success' => false, 'error' => 'New password is required']);
    exit;
}

// Check if passwords match
if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'error' => 'New password and confirm password do not match']);
    exit;
}

// Validate new password length
$password_length = strlen($new_password);
if ($password_length < 6) {
    echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters long']);
    exit;
}

if ($password_length > 100) {
    echo json_encode(['success' => false, 'error' => 'New password is too long (maximum 100 characters)']);
    exit;
}

// Check password strength
$has_upper = preg_match('/[A-Z]/', $new_password);
$has_lower = preg_match('/[a-z]/', $new_password);
$has_number = preg_match('/[0-9]/', $new_password);
$has_special = preg_match('/[^a-zA-Z0-9]/', $new_password);

$strength = ($has_upper ? 1 : 0) + ($has_lower ? 1 : 0) + ($has_number ? 1 : 0) + ($has_special ? 1 : 0);

if ($strength < 2) {
    echo json_encode([
        'success' => false, 
        'error' => 'Password must contain at least letters and numbers. Use a mix of uppercase, lowercase, and numbers for better security.'
    ]);
    exit;
}

// Provide strength feedback
$strength_message = '';
if ($strength >= 3) {
    $strength_message = 'Strong password';
} elseif ($strength == 2) {
    $strength_message = 'Medium password';
} else {
    $strength_message = 'Weak password';
}

// ========== GET CURRENT PASSWORD HASH ==========
$stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? AND role = 'partner'");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Partner account not found']);
    exit;
}

// ========== VERIFY CURRENT PASSWORD ==========
if (!password_verify($current_password, $user['password'])) {
    // Log failed attempt for security
    $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'failed_password_change', ?, NOW())");
    if ($log_stmt) {
        $description = "Failed password change attempt - incorrect current password";
        mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    }
    
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
    exit;
}

// ========== PREVENT SAME PASSWORD ==========
if (password_verify($new_password, $user['password'])) {
    echo json_encode(['success' => false, 'error' => 'New password cannot be the same as current password']);
    exit;
}

// ========== UPDATE PASSWORD ==========
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ? AND role = 'partner'");
if (!$update_stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed for update: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $partner_id);

if (mysqli_stmt_execute($update_stmt)) {
    // Clear all existing sessions for this user (optional - security feature)
    // DELETE FROM user_sessions WHERE user_id = $partner_id;
    
    // Log successful password change
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'change_password', ?, NOW())");
        if ($log_stmt) {
            $description = "Successfully changed account password";
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
    // Clear any remember me tokens
    $checkSessionsTable = mysqli_query($conn, "SHOW TABLES LIKE 'user_sessions'");
    if (mysqli_num_rows($checkSessionsTable) > 0) {
        $clearSessions = mysqli_prepare($conn, "DELETE FROM user_sessions WHERE user_id = ?");
        if ($clearSessions) {
            mysqli_stmt_bind_param($clearSessions, "i", $partner_id);
            mysqli_stmt_execute($clearSessions);
            mysqli_stmt_close($clearSessions);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully! Please login again with your new password.',
        'password_strength' => $strength_message,
        'requires_relogin' => true
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update password: ' . mysqli_error($conn)]);
}

// ========== CLEAN UP ==========
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($update_stmt)) mysqli_stmt_close($update_stmt);
if (isset($role_check)) mysqli_stmt_close($role_check);

mysqli_close($conn);
?>