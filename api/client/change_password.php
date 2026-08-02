<?php
// api/client/change_password.php - Change client password
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get client_id (only client can change their own password)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';

// Only client can change password
if ($viewer_role !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Only clients can change their own password']);
    exit;
}

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

// ========== GET INPUT ==========
$current_password = $input['current_password'] ?? '';
$new_password = $input['new_password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

// ========== VALIDATION ==========
$errors = [];

// Check if all fields are provided
if (empty($current_password)) {
    $errors[] = "Current password is required";
}
if (empty($new_password)) {
    $errors[] = "New password is required";
}
if (empty($confirm_password)) {
    $errors[] = "Please confirm your new password";
}

// Password strength validation
if (!empty($new_password)) {
    if (strlen($new_password) < 8) {
        $errors[] = "New password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $new_password)) {
        $errors[] = "New password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $new_password)) {
        $errors[] = "New password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $new_password)) {
        $errors[] = "New password must contain at least one number";
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
        $errors[] = "New password must contain at least one special character (!@#$%^&* etc.)";
    }
}

// Check if new password matches confirmation
if (!empty($new_password) && !empty($confirm_password) && $new_password !== $confirm_password) {
    $errors[] = "New password and confirmation do not match";
}

// Check if new password is same as old password (optional but recommended)
if (!empty($current_password) && !empty($new_password) && $current_password === $new_password) {
    $errors[] = "New password cannot be the same as current password";
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ========== VERIFY CURRENT PASSWORD ==========
$query = "SELECT id, password FROM users WHERE id = ? AND role = 'client'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Verify current password
if (!password_verify($current_password, $user['password'])) {
    // Log failed attempt
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $log_activity = mysqli_prepare($conn, "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address, user_agent) VALUES (?, 'password_change_failed', ?, ?, ?)");
    $desc = "Failed password change attempt - incorrect current password";
    mysqli_stmt_bind_param($log_activity, "isss", $client_id, $desc, $ip_address, $user_agent);
    mysqli_stmt_execute($log_activity);
    mysqli_stmt_close($log_activity);
    
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
    exit;
}

// ========== CHECK PASSWORD HISTORY (PREVENT REUSE) ==========
// Create password history table if not exists
$create_history = "CREATE TABLE IF NOT EXISTS password_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
)";
mysqli_query($conn, $create_history);

// Check last 5 passwords for reuse
$history_check = mysqli_prepare($conn, "SELECT password_hash FROM password_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
mysqli_stmt_bind_param($history_check, "i", $client_id);
mysqli_stmt_execute($history_check);
$history_result = mysqli_stmt_get_result($history_check);
$previous_passwords = mysqli_fetch_all($history_result, MYSQLI_ASSOC);
mysqli_stmt_close($history_check);

foreach ($previous_passwords as $prev) {
    if (password_verify($new_password, $prev['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'You have used this password recently. Please choose a different password']);
        exit;
    }
}

// ========== HASH NEW PASSWORD ==========
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// ========== UPDATE PASSWORD ==========
$update_query = "UPDATE users SET password = ? WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($update_stmt, "si", $new_password_hash, $client_id);
$updated = mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

if (!$updated) {
    echo json_encode(['success' => false, 'error' => 'Failed to update password. Please try again.']);
    exit;
}

// ========== SAVE TO PASSWORD HISTORY ==========
$save_history = mysqli_prepare($conn, "INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)");
mysqli_stmt_bind_param($save_history, "is", $client_id, $new_password_hash);
mysqli_stmt_execute($save_history);
mysqli_stmt_close($save_history);

// ========== CLEAR ALL OTHER SESSIONS (OPTIONAL) ==========
// Invalidate all other sessions for this user (for security)
// This forces logout from other devices
$clear_sessions = mysqli_prepare($conn, "DELETE FROM user_sessions WHERE user_id = ? AND session_id != ?");
$current_session_id = session_id();
mysqli_stmt_bind_param($clear_sessions, "is", $client_id, $current_session_id);
mysqli_stmt_execute($clear_sessions);
mysqli_stmt_close($clear_sessions);

// ========== LOG THE ACTIVITY ==========
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$log_activity = mysqli_prepare($conn, "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address, user_agent) VALUES (?, 'password_changed', ?, ?, ?)");
$desc = "Password changed successfully";
mysqli_stmt_bind_param($log_activity, "isss", $client_id, $desc, $ip_address, $user_agent);
mysqli_stmt_execute($log_activity);
mysqli_stmt_close($log_activity);

// ========== SEND EMAIL NOTIFICATION (OPTIONAL) ==========
// You can implement email notification here
// sendPasswordChangeEmail($user_email, $user_name);

// ========== UPDATE SESSION ==========
// Optionally regenerate session ID after password change
session_regenerate_id(true);
$_SESSION['password_changed_at'] = time();

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'message' => 'Password changed successfully',
    'note' => 'You have been logged out from other devices for security'
]);

mysqli_close($conn);
?>