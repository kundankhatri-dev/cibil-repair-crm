<?php
// api/client/login.php - Client Login API (Compatible with main system)
session_start();
header('Content-Type: application/json');

// Rate limiting - prevent brute force
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = 'login_attempts_' . $ip_address;

// Simple rate limiting using session
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Reset after 15 minutes
if (time() - $_SESSION['last_attempt_time'] > 900) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SESSION['login_attempts'] >= 5) {
    echo json_encode([
        'success' => false, 
        'error' => 'Too many login attempts. Please try again after 15 minutes.'
    ]);
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

// Get input data
$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$remember_me = isset($data['remember_me']) ? (bool)$data['remember_me'] : false;

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Email and password required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

// Query user
$query = "SELECT id, name, email, phone, password, role, status, created_at 
          FROM users 
          WHERE email = ? AND role = 'client'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt_time'] = time();
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    exit;
}

// Check account status
if ($user['status'] != 'active') {
    $status_message = $user['status'] === 'pending' ? 'Account pending approval' : 'Account is inactive. Contact support.';
    echo json_encode(['success' => false, 'error' => $status_message]);
    exit;
}

// Verify password
if (password_verify($password, $user['password'])) {
    // Reset login attempts on success
    $_SESSION['login_attempts'] = 0;
    
    // ========== SET SESSION VARIABLES (Compatible with main system) ==========
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = 'client';
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Also keep client-specific session vars for backward compatibility
    $_SESSION['client_id'] = $user['id'];
    $_SESSION['client_name'] = $user['name'];
    $_SESSION['client_email'] = $user['email'];
    $_SESSION['client_logged_in'] = true;
    
    // ========== HANDLE REMEMBER ME ==========
    if ($remember_me) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Create remember_tokens table if not exists
        $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'user_tokens'");
        if (mysqli_num_rows($checkTable) == 0) {
            $createTable = "CREATE TABLE IF NOT EXISTS user_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_token (token)
            )";
            mysqli_query($conn, $createTable);
        }
        
        // Store token
        $storeToken = mysqli_prepare($conn, "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($storeToken, "iss", $user['id'], $token, $expires);
        mysqli_stmt_execute($storeToken);
        mysqli_stmt_close($storeToken);
        
        // Set cookie
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
    }
    
    // ========== UPDATE LAST LOGIN ==========
    $updateLogin = mysqli_prepare($conn, "UPDATE users SET last_login = NOW(), last_login_ip = ? WHERE id = ?");
    mysqli_stmt_bind_param($updateLogin, "si", $ip_address, $user['id']);
    mysqli_stmt_execute($updateLogin);
    mysqli_stmt_close($updateLogin);
    
    // ========== LOG LOGIN HISTORY ==========
    $historyTable = 'login_history';
    $checkHistoryTable = mysqli_query($conn, "SHOW TABLES LIKE '$historyTable'");
    if (mysqli_num_rows($checkHistoryTable) > 0) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $logHistory = mysqli_prepare($conn, "INSERT INTO $historyTable (user_id, login_time, ip_address, user_agent, success) VALUES (?, NOW(), ?, ?, 1)");
        mysqli_stmt_bind_param($logHistory, "iss", $user['id'], $ip_address, $userAgent);
        mysqli_stmt_execute($logHistory);
        mysqli_stmt_close($logHistory);
    }
    
    // Remove password from output
    unset($user['password']);
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'redirect' => 'client-dashboard.php',  // Changed from .html to .php
        'message' => 'Login successful'
    ]);
} else {
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt_time'] = time();
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid email or password',
        'attempts_remaining' => 5 - $_SESSION['login_attempts']
    ]);
}

mysqli_close($conn);
?>