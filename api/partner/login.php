<?php
// api/partner/login.php
// Partner Login API - Authenticate partner and start session

// Error reporting (off for production)
error_reporting(0);
ini_set('display_errors', 0);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== GET INPUT ==========
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$remember_me = isset($input['remember_me']) ? (bool)$input['remember_me'] : false;

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Email and password are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

// ========== CHECK LOGIN ATTEMPTS ==========
$attemptTable = 'login_attempts';
$checkAttemptTable = mysqli_query($conn, "SHOW TABLES LIKE '$attemptTable'");
if (mysqli_num_rows($checkAttemptTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS $attemptTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        attempt_time DATETIME NOT NULL,
        INDEX idx_email (email),
        INDEX idx_ip (ip_address)
    )";
    mysqli_query($conn, $createTable);
}

$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Check recent failed attempts (last 15 minutes)
$checkAttempts = mysqli_prepare($conn, "SELECT COUNT(*) as attempts FROM $attemptTable WHERE email = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
mysqli_stmt_bind_param($checkAttempts, "s", $email);
mysqli_stmt_execute($checkAttempts);
$result = mysqli_stmt_get_result($checkAttempts);
$attempt_data = mysqli_fetch_assoc($result);
$failed_attempts = $attempt_data['attempts'] ?? 0;
mysqli_stmt_close($checkAttempts);

if ($failed_attempts >= 5) {
    echo json_encode(['success' => false, 'error' => 'Too many failed attempts. Please try again after 15 minutes.']);
    exit;
}

// ========== QUERY USER WITH PREPARED STATEMENT ==========
$query = "SELECT id, name, email, phone, password, role, status FROM users WHERE email = ? AND role = 'partner'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    // Log failed attempt
    $logAttempt = mysqli_prepare($conn, "INSERT INTO $attemptTable (email, ip_address, attempt_time) VALUES (?, ?, NOW())");
    mysqli_stmt_bind_param($logAttempt, "ss", $email, $ip_address);
    mysqli_stmt_execute($logAttempt);
    mysqli_stmt_close($logAttempt);
    
    echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    exit;
}

if ($user['status'] !== 'active') {
    echo json_encode(['success' => false, 'error' => 'Your account is not active. Please contact support.']);
    exit;
}

// ========== VERIFY PASSWORD ==========
if (!password_verify($password, $user['password'])) {
    // Log failed attempt
    $logAttempt = mysqli_prepare($conn, "INSERT INTO $attemptTable (email, ip_address, attempt_time) VALUES (?, ?, NOW())");
    mysqli_stmt_bind_param($logAttempt, "ss", $email, $ip_address);
    mysqli_stmt_execute($logAttempt);
    mysqli_stmt_close($logAttempt);
    
    $remaining_attempts = 4 - $failed_attempts;
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid email or password',
        'remaining_attempts' => max(0, $remaining_attempts)
    ]);
    exit;
}

// ========== LOGIN SUCCESS ==========

// Clear failed attempts
$clearAttempts = mysqli_prepare($conn, "DELETE FROM $attemptTable WHERE email = ?");
mysqli_stmt_bind_param($clearAttempts, "s", $email);
mysqli_stmt_execute($clearAttempts);
mysqli_stmt_close($clearAttempts);

// Update last login (check if column exists)
$checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'last_login'");
if (mysqli_num_rows($checkColumn) > 0) {
    $updateLogin = mysqli_prepare($conn, "UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?");
    mysqli_stmt_bind_param($updateLogin, "si", $ip_address, $user['id']);
    mysqli_stmt_execute($updateLogin);
    mysqli_stmt_close($updateLogin);
}

// Update last_ip column if exists
$checkIpColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'last_ip'");
if (mysqli_num_rows($checkIpColumn) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) DEFAULT NULL");
}

// ========== START SESSION ==========
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();

// ========== HANDLE REMEMBER ME ==========
if ($remember_me) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Create remember_tokens table if not exists
    $checkTokenTable = mysqli_query($conn, "SHOW TABLES LIKE 'user_tokens'");
    if (mysqli_num_rows($checkTokenTable) == 0) {
        $createTokenTable = "CREATE TABLE IF NOT EXISTS user_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_token (token),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        mysqli_query($conn, $createTokenTable);
    }
    
    // Store token
    $storeToken = mysqli_prepare($conn, "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($storeToken, "iss", $user['id'], $token, $expires);
    mysqli_stmt_execute($storeToken);
    mysqli_stmt_close($storeToken);
    
    // Set cookie (30 days)
    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
}

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

// ========== GET PARTNER ADDITIONAL INFO ==========
$partner_info = [];
$partnerQuery = "SELECT company_name, commission_rate, total_leads, total_converted, total_commission, pending_payout, referral_code 
                 FROM partners WHERE user_id = ?";
$partnerStmt = mysqli_prepare($conn, $partnerQuery);
mysqli_stmt_bind_param($partnerStmt, "i", $user['id']);
mysqli_stmt_execute($partnerStmt);
$partnerResult = mysqli_stmt_get_result($partnerStmt);
$partner_info = mysqli_fetch_assoc($partnerResult);
mysqli_stmt_close($partnerStmt);

// ========== PREPARE RESPONSE ==========
$response_user = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'phone' => $user['phone'],
    'role' => $user['role'],
    'company_name' => $partner_info['company_name'] ?? null,
    'commission_rate' => (float)($partner_info['commission_rate'] ?? 10),
    'total_leads' => (int)($partner_info['total_leads'] ?? 0),
    'total_converted' => (int)($partner_info['total_converted'] ?? 0),
    'total_commission' => (float)($partner_info['total_commission'] ?? 0),
    'pending_payout' => (float)($partner_info['pending_payout'] ?? 0),
    'referral_code' => $partner_info['referral_code'] ?? null
];

// Remove password from output
unset($user['password']);

echo json_encode([
    'success' => true,
    'user' => $response_user,
    'session_id' => session_id(),
    'redirect' => 'partner-dashboard.html',
    'message' => 'Login successful'
]);

mysqli_close($conn);
?>