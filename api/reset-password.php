<?php
// ============================================================
// RESET PASSWORD API
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$confirm = isset($input['confirm_password']) ? $input['confirm_password'] : '';

// Validate
if (empty($email) || empty($password) || empty($confirm)) {
    echo json_encode(['success' => false, 'error' => 'Email, password and confirm password are required']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
    exit;
}

if ($password !== $confirm) {
    echo json_encode(['success' => false, 'error' => 'Passwords do not match']);
    exit;
}

// Find user
$sql = "SELECT id, name, email FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    mysqli_close($conn);
    exit;
}

// Hash and update password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$updateSql = "UPDATE users SET password = ? WHERE id = ?";
$updateStmt = mysqli_prepare($conn, $updateSql);
mysqli_stmt_bind_param($updateStmt, 'si', $hashedPassword, $user['id']);
mysqli_stmt_execute($updateStmt);
$affected = mysqli_stmt_affected_rows($updateStmt);
mysqli_stmt_close($updateStmt);

mysqli_close($conn);

if ($affected > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully! You can now login.',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update password. Please try again.'
    ]);
}
?>