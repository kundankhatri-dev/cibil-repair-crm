<?php
// api/partner/check_email.php
// Partner Check Email API - Check if email is already registered (for registration form)

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

// ========== RATE LIMITING (Prevent abuse) ==========
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = 'email_check_' . $ip_address;

// Simple rate limiting using session or cache
if (!isset($_SESSION['email_check_count'])) {
    $_SESSION['email_check_count'] = 0;
    $_SESSION['email_check_time'] = time();
}

// Reset count after 1 minute
if (time() - $_SESSION['email_check_time'] > 60) {
    $_SESSION['email_check_count'] = 0;
    $_SESSION['email_check_time'] = time();
}

// Check rate limit (max 10 checks per minute)
if ($_SESSION['email_check_count'] >= 10) {
    echo json_encode([
        'success' => false, 
        'error' => 'Too many requests. Please wait a moment.',
        'rate_limited' => true
    ]);
    exit;
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

// Validate email length
if (strlen($email) > 255) {
    echo json_encode(['success' => false, 'error' => 'Email is too long']);
    exit;
}

// ========== CHECK IF EMAIL EXISTS ==========
// Also check status to give better feedback
$check_stmt = mysqli_prepare($conn, "SELECT id, name, status FROM users WHERE email = ? AND role = 'partner'");
mysqli_stmt_bind_param($check_stmt, "s", $email);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);
$exists = mysqli_stmt_num_rows($check_stmt) > 0;

$status = null;
$name = null;

if ($exists) {
    mysqli_stmt_bind_result($check_stmt, $user_id, $name, $status);
    mysqli_stmt_fetch($check_stmt);
}
mysqli_stmt_close($check_stmt);

// Increment rate limit counter
$_SESSION['email_check_count']++;

// ========== RETURN RESPONSE ==========
$response = [
    'success' => true,
    'exists' => $exists,
    'message' => $exists ? 'Email is already registered' : 'Email is available',
    'available' => !$exists
];

if ($exists) {
    $response['status'] = $status;
    if ($status === 'pending') {
        $response['message'] = 'Email is registered but pending approval. Please wait for admin approval.';
        $response['suggestion'] = 'Contact admin for status update.';
    } elseif ($status === 'active') {
        $response['message'] = 'Email is already registered. Please login or reset password.';
        $response['suggestion'] = 'Try logging in or use "Forgot Password".';
    } elseif ($status === 'inactive') {
        $response['message'] = 'Account is inactive. Please contact support.';
        $response['suggestion'] = 'Contact admin to reactivate your account.';
    }
    
    // Mask name for privacy (show only first character and last)
    if ($name) {
        $name_parts = explode(' ', $name);
        $masked_name = '';
        foreach ($name_parts as $part) {
            if (strlen($part) > 2) {
                $masked_name .= substr($part, 0, 1) . str_repeat('*', max(1, strlen($part) - 2)) . substr($part, -1) . ' ';
            } else {
                $masked_name .= $part[0] . str_repeat('*', max(1, strlen($part) - 1)) . ' ';
            }
        }
        $response['registered_name'] = trim($masked_name);
    }
}

// Rate limit remaining
$response['rate_limit_remaining'] = 10 - $_SESSION['email_check_count'];
$response['rate_limit_reset'] = 60 - (time() - $_SESSION['email_check_time']);

echo json_encode($response);

mysqli_close($conn);
?>