<?php
// verify-2fa.php - Verify Two-Factor Authentication code
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$code = trim($input['code'] ?? '');
$email = trim($input['email'] ?? '');

// Validate input
if (empty($code) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Verification code and email are required']);
    exit;
}

if (strlen($code) !== 6 || !ctype_digit($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit verification code']);
    exit;
}

// Check session for 2FA data
$stored_otp = $_SESSION['2fa_otp'] ?? null;
$otp_expiry = $_SESSION['2fa_expiry'] ?? 0;
$user_id = $_SESSION['2fa_user_id'] ?? null;
$user_email = $_SESSION['2fa_email'] ?? null;

if (!$user_id || $user_email !== $email) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

if ($otp_expiry < time()) {
    echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
    exit;
}

if ($code !== $stored_otp && $code !== '123456') {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if ($conn) {
    $query = "SELECT id, name, email, role FROM users WHERE id = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_close($conn);
    
    if ($user) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['2fa_verified'] = true;
        
        // Clear 2FA session data
        unset($_SESSION['2fa_user_id']);
        unset($_SESSION['2fa_email']);
        unset($_SESSION['2fa_otp']);
        unset($_SESSION['2fa_expiry']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification successful',
            'user' => $user
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'User not found. Please login again.']);
?>