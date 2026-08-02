<?php
// api/partner/forgot_password.php
// Partner Forgot Password API - Request password reset link

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

// ========== RATE LIMITING ==========
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = 'password_reset_' . $ip_address;

// Check rate limit in database
$rateTable = "CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action VARCHAR(50) NOT NULL,
    request_count INT DEFAULT 1,
    first_request DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_request DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_action (ip_address, action)
)";
mysqli_query($conn, $rateTable);

// Check recent requests
$check_rate = mysqli_prepare($conn, "SELECT request_count, first_request FROM rate_limits WHERE ip_address = ? AND action = 'forgot_password' AND last_request > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
if ($check_rate) {
    mysqli_stmt_bind_param($check_rate, "s", $ip_address);
    mysqli_stmt_execute($check_rate);
    $rate_result = mysqli_stmt_get_result($check_rate);
    $rate_data = mysqli_fetch_assoc($rate_result);
    
    if ($rate_data && $rate_data['request_count'] >= 5) {
        echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']);
        exit;
    }
    mysqli_stmt_close($check_rate);
}

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

// ========== VALIDATE INPUT ==========
if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

// ========== CHECK IF USER EXISTS ==========
$query = "SELECT u.id, u.name, u.email, u.status FROM users u WHERE u.email = ? AND u.role = 'partner'";
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    // Don't reveal that email doesn't exist for security
    echo json_encode(['success' => true, 'message' => 'If your email is registered, you will receive a password reset link.']);
    exit;
}

if ($user['status'] !== 'active') {
    echo json_encode(['success' => false, 'error' => 'Your account is not active. Please contact support.']);
    exit;
}

// ========== CHECK FOR EXISTING VALID TOKEN ==========
$check_token = mysqli_prepare($conn, "SELECT id, expires_at FROM password_resets WHERE user_id = ? AND used = 0 AND expires_at > NOW()");
if ($check_token) {
    mysqli_stmt_bind_param($check_token, "i", $user['id']);
    mysqli_stmt_execute($check_token);
    $token_result = mysqli_stmt_get_result($check_token);
    $existing_token = mysqli_fetch_assoc($token_result);
    
    if ($existing_token) {
        // Token already exists and is valid
        echo json_encode(['success' => true, 'message' => 'A password reset link has already been sent. Please check your email.']);
        exit;
    }
    mysqli_stmt_close($check_token);
}

// ========== GENERATE RESET TOKEN ==========
$reset_token = bin2hex(random_bytes(32));
$token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Create password_resets table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_user (user_id)
)";
mysqli_query($conn, $create_table);

// Delete any expired tokens for this user
$delete_expired = mysqli_prepare($conn, "DELETE FROM password_resets WHERE user_id = ? AND expires_at < NOW()");
if ($delete_expired) {
    mysqli_stmt_bind_param($delete_expired, "i", $user['id']);
    mysqli_stmt_execute($delete_expired);
    mysqli_stmt_close($delete_expired);
}

// Insert new token
$insert_stmt = mysqli_prepare($conn, "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
if (!$insert_stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

mysqli_stmt_bind_param($insert_stmt, "iss", $user['id'], $reset_token, $token_expiry);
mysqli_stmt_execute($insert_stmt);
mysqli_stmt_close($insert_stmt);

// ========== UPDATE RATE LIMIT ==========
if ($rate_data) {
    $update_rate = mysqli_prepare($conn, "UPDATE rate_limits SET request_count = request_count + 1, last_request = NOW() WHERE ip_address = ? AND action = 'forgot_password'");
    mysqli_stmt_bind_param($update_rate, "s", $ip_address);
    mysqli_stmt_execute($update_rate);
    mysqli_stmt_close($update_rate);
} else {
    $insert_rate = mysqli_prepare($conn, "INSERT INTO rate_limits (ip_address, action, request_count, first_request, last_request) VALUES (?, 'forgot_password', 1, NOW(), NOW())");
    mysqli_stmt_bind_param($insert_rate, "s", $ip_address);
    mysqli_stmt_execute($insert_rate);
    mysqli_stmt_close($insert_rate);
}

// ========== GENERATE RESET LINK ==========
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $protocol . $_SERVER['HTTP_HOST'];
$reset_link = $domain . '/reset-password.html?token=' . $reset_token . '&email=' . urlencode($email);

// ========== SEND EMAIL ==========
// Configure SMTP or mail function
$subject = "Password Reset Request - CIBIL Repair Partner Portal";

$message = "
<html>
<head>
    <title>Password Reset Request</title>
</head>
<body>
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
        <div style='text-align: center; padding-bottom: 20px; border-bottom: 2px solid #1f8a72;'>
            <h2 style='color: #1f8a72;'>CIBIL Repair</h2>
            <p style='color: #666;'>Partner Portal</p>
        </div>
        
        <div style='padding: 20px 0;'>
            <p>Dear <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
            <p>We received a request to reset your password for your partner account.</p>
            <p>Click the button below to reset your password:</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . $reset_link . "' style='background-color: #1f8a72; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
            </div>
            
            <p>Or copy and paste this link in your browser:</p>
            <p style='background-color: #f5f5f5; padding: 10px; word-break: break-all; font-size: 12px;'>" . $reset_link . "</p>
            
            <p><strong>Note:</strong> This link will expire in <strong>1 hour</strong>.</p>
            <p>If you did not request this, please ignore this email. Your password will remain unchanged.</p>
        </div>
        
        <div style='text-align: center; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999;'>
            <p>© " . date('Y') . " CIBIL Repair. All rights reserved.</p>
            <p>Need help? Contact us at support@cibilrepair.in</p>
        </div>
    </div>
</body>
</html>
";

// Plain text alternative
$plain_message = "Dear " . $user['name'] . ",\n\n";
$plain_message .= "We received a request to reset your password for your partner account.\n\n";
$plain_message .= "Click the link below to reset your password:\n\n";
$plain_message .= $reset_link . "\n\n";
$plain_message .= "This link will expire in 1 hour.\n\n";
$plain_message .= "If you did not request this, please ignore this email.\n\n";
$plain_message .= "Thanks,\nCIBIL Repair Team";

// Headers for HTML email
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: CIBIL Repair <support@cibilrepair.in>\r\n";
$headers .= "Reply-To: support@cibilrepair.in\r\n";

// ========== SEND EMAIL ==========
$email_sent = false;
$mail_sent = mail($email, $subject, $message, $headers);

if ($mail_sent) {
    $email_sent = true;
} else {
    // Fallback to plain text if HTML fails
    $mail_sent = mail($email, $subject, $plain_message, "From: support@cibilrepair.in\r\n");
    $email_sent = $mail_sent;
}

// ========== LOG ACTIVITY ==========
$checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
if (mysqli_num_rows($checkActivityTable) > 0) {
    $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'forgot_password', ?, NOW())");
    if ($log_stmt) {
        $description = "Password reset requested" . ($email_sent ? "" : " (email failed)");
        mysqli_stmt_bind_param($log_stmt, "is", $user['id'], $description);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    }
}

// ========== RETURN RESPONSE ==========
$response = [
    'success' => true,
    'message' => 'If your email is registered, you will receive a password reset link.'
];

// Only include reset link in debug mode (remove in production)
// if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
//     $response['reset_link'] = $reset_link;
// }

echo json_encode($response);

mysqli_close($conn);
?>