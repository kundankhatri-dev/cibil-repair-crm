<?php
// api/partner/reset_password.php
// Partner Reset Password API - Complete password reset with token

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

// ========== ENSURE TABLES EXIST ==========
// Password resets table
$resetsTable = 'password_resets';
$checkResetsTable = mysqli_query($conn, "SHOW TABLES LIKE '$resetsTable'");
if (mysqli_num_rows($checkResetsTable) == 0) {
    $createResets = "CREATE TABLE IF NOT EXISTS $resetsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_user_id (user_id),
        INDEX idx_expires_at (expires_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createResets);
}

// Activities table
$activitiesTable = 'activities';
$checkActivitiesTable = mysqli_query($conn, "SHOW TABLES LIKE '$activitiesTable'");
if (mysqli_num_rows($checkActivitiesTable) == 0) {
    $createActivities = "CREATE TABLE IF NOT EXISTS $activitiesTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activity_type VARCHAR(50),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    )";
    mysqli_query($conn, $createActivities);
}

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);
$token = trim($data['token'] ?? '');
$email = trim($data['email'] ?? '');
$new_password = $data['new_password'] ?? '';
$confirm_password = $data['confirm_password'] ?? '';

// ========== VALIDATE INPUT ==========
$errors = [];

if (empty($token)) {
    $errors[] = 'Reset token is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (empty($new_password)) {
    $errors[] = 'New password is required';
} elseif (strlen($new_password) < 6) {
    $errors[] = 'Password must be at least 6 characters';
} elseif (strlen($new_password) > 100) {
    $errors[] = 'Password is too long (maximum 100 characters)';
}

// Password strength check
$password_strength = 0;
if (preg_match('/[A-Z]/', $new_password)) $password_strength++;
if (preg_match('/[a-z]/', $new_password)) $password_strength++;
if (preg_match('/[0-9]/', $new_password)) $password_strength++;
if (preg_match('/[^a-zA-Z0-9]/', $new_password)) $password_strength++;

if ($password_strength < 2) {
    $errors[] = 'Password should contain at least letters and numbers';
}

if ($new_password !== $confirm_password) {
    $errors[] = 'Passwords do not match';
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors, 'error_count' => count($errors)]);
    exit;
}

// ========== VERIFY TOKEN ==========
$query = "SELECT pr.user_id, pr.token, pr.expires_at, u.email, u.name, u.status, u.password as current_password
          FROM $resetsTable pr
          JOIN users u ON pr.user_id = u.id
          WHERE pr.token = ? AND u.email = ? AND u.role = 'partner' AND pr.used = 0";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $token, $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$reset_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$reset_data) {
    echo json_encode(['success' => false, 'error' => 'Invalid or expired reset token']);
    exit;
}

// Check if token expired
if (strtotime($reset_data['expires_at']) < time()) {
    echo json_encode(['success' => false, 'error' => 'Reset token has expired. Please request a new one.']);
    exit;
}

// Check account status
if ($reset_data['status'] !== 'active') {
    echo json_encode(['success' => false, 'error' => 'Your account is not active. Please contact support.']);
    exit;
}

// ========== PREVENT PASSWORD REUSE ==========
if (password_verify($new_password, $reset_data['current_password'])) {
    echo json_encode(['success' => false, 'error' => 'New password cannot be the same as your current password']);
    exit;
}

// ========== HASH NEW PASSWORD ==========
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// ========== START TRANSACTION ==========
mysqli_begin_transaction($conn);

// ========== UPDATE PASSWORD ==========
$update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $reset_data['user_id']);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

// ========== MARK TOKEN AS USED ==========
$mark_stmt = mysqli_prepare($conn, "UPDATE $resetsTable SET used = 1 WHERE token = ?");
mysqli_stmt_bind_param($mark_stmt, "s", $token);
mysqli_stmt_execute($mark_stmt);
mysqli_stmt_close($mark_stmt);

// ========== CLEAR ALL EXISTING SESSIONS FOR THIS USER ==========
// Delete remember me tokens
$checkTokenTable = mysqli_query($conn, "SHOW TABLES LIKE 'user_tokens'");
if (mysqli_num_rows($checkTokenTable) > 0) {
    $clearTokens = mysqli_prepare($conn, "DELETE FROM user_tokens WHERE user_id = ?");
    mysqli_stmt_bind_param($clearTokens, "i", $reset_data['user_id']);
    mysqli_stmt_execute($clearTokens);
    mysqli_stmt_close($clearTokens);
}

// ========== LOG ACTIVITY ==========
$log_stmt = mysqli_prepare($conn, "INSERT INTO $activitiesTable (user_id, activity_type, description, created_at) VALUES (?, 'reset_password', ?, NOW())");
$description = "Password reset successfully completed";
mysqli_stmt_bind_param($log_stmt, "is", $reset_data['user_id'], $description);
mysqli_stmt_execute($log_stmt);
mysqli_stmt_close($log_stmt);

// ========== COMMIT TRANSACTION ==========
mysqli_commit($conn);

// ========== DESTROY ANY CURRENT SESSION ==========
// If the user is currently logged in, log them out
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $reset_data['user_id']) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    session_start(); // Start new session for response
}

// ========== SEND CONFIRMATION EMAIL (Optional) ==========
// $subject = "Password Changed Successfully";
// $message = "Dear " . $reset_data['name'] . ",\n\n";
// $message .= "Your password has been successfully changed.\n\n";
// $message .= "If you did not perform this action, please contact support immediately.\n\n";
// $message .= "Thanks,\nCIBIL Repair Team";
// mail($email, $subject, $message, "From: support@cibilrepair.in");

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'message' => 'Password reset successfully! Please login with your new password.',
    'user_name' => $reset_data['name']
]);

mysqli_close($conn);
?>