<?php
// api/employee/change_password.php - Change employee password
session_start();
header('Content-Type: application/json');

// Allow only employees
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

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

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = isset($input['current_password']) ? $input['current_password'] : '';
$new_password = isset($input['new_password']) ? $input['new_password'] : '';
$confirm_password = isset($input['confirm_password']) ? $input['confirm_password'] : '';

// Validation
$errors = [];

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
    if (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long";
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
}

if (!empty($new_password) && !empty($confirm_password) && $new_password !== $confirm_password) {
    $errors[] = "New password and confirmation do not match";
}

if (!empty($current_password) && !empty($new_password) && $current_password === $new_password) {
    $errors[] = "New password cannot be the same as current password";
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Get current user password from database
$query = "SELECT id, password, name, email FROM users WHERE id = ? AND role = 'employee'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
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
    $log_activity = mysqli_prepare($conn, "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address) VALUES (?, 'password_change_failed', ?, ?)");
    $desc = "Failed password change attempt - incorrect current password";
    mysqli_stmt_bind_param($log_activity, "iss", $user_id, $desc, $ip_address);
    mysqli_stmt_execute($log_activity);
    mysqli_stmt_close($log_activity);
    
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
    exit;
}

// Hash new password
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update password
$update_query = "UPDATE users SET password = ? WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($update_stmt, "si", $new_password_hash, $user_id);
$updated = mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

if (!$updated) {
    echo json_encode(['success' => false, 'error' => 'Failed to update password. Please try again.']);
    exit;
}

// Update password history (optional - create table if not exists)
$create_history = "CREATE TABLE IF NOT EXISTS password_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id)
)";
mysqli_query($conn, $create_history);

$save_history = mysqli_prepare($conn, "INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)");
mysqli_stmt_bind_param($save_history, "is", $user_id, $new_password_hash);
mysqli_stmt_execute($save_history);
mysqli_stmt_close($save_history);

// Log successful password change
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$log_activity = mysqli_prepare($conn, "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address, user_agent) VALUES (?, 'password_changed', ?, ?, ?)");
$desc = "Password changed successfully";
mysqli_stmt_bind_param($log_activity, "isss", $user_id, $desc, $ip_address, $user_agent);
mysqli_stmt_execute($log_activity);
mysqli_stmt_close($log_activity);

// Regenerate session ID for security
session_regenerate_id(true);
$_SESSION['password_changed_at'] = time();

echo json_encode([
    'success' => true,
    'message' => 'Password changed successfully',
    'note' => 'Please use your new password for future logins'
]);

mysqli_close($conn);
?>