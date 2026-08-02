<?php
// ============================================================
// LOGIN PROCESSOR - Enhanced with Security Features
// Supports: Rate Limiting, 2FA, CSRF, Device Tracking
// ============================================================

// ========== SESSION CONFIGURATION ==========
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Lax');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
header('Content-Type: application/json');

// ========== CSRF TOKEN VALIDATION ==========
$headers = getallheaders();
$csrf_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $headers['X-CSRF-Token'] ?? '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!empty($csrf_token) && $csrf_token !== $_SESSION['csrf_token']) {
    echo json_encode([
        'success' => false, 
        'message' => 'Security validation failed. Please refresh the page and try again.'
    ]);
    exit;
}

// ========== RATE LIMITING ==========
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = 'login_attempts_' . str_replace('.', '_', $ip_address);

// Initialize rate limiting in session if not exists
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Reset after 15 minutes
if (time() - $_SESSION['last_attempt_time'] > 900) {
    $_SESSION['login_attempts'] = 0;
}

// Check rate limit (max 5 attempts per 15 minutes)
if ($_SESSION['login_attempts'] >= 5) {
    $wait_time = 900 - (time() - $_SESSION['last_attempt_time']);
    $wait_minutes = ceil($wait_time / 60);
    echo json_encode([
        'success' => false, 
        'message' => "Too many login attempts. Please wait {$wait_minutes} minutes before trying again."
    ]);
    exit;
}

// ========== GET INPUT DATA ==========
$email = '';
$password_input = '';
$remember_me = false;
$captcha_input = '';

// Check POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password_input = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']) ? true : false;
    $captcha_input = strtoupper(trim($_POST['captcha'] ?? ''));
}
// Check GET data (for backward compatibility)
else if (isset($_GET['email'])) {
    $email = strtolower(trim($_GET['email']));
    $password_input = $_GET['password'] ?? '';
    $remember_me = isset($_GET['remember_me']) ? true : false;
}

// ========== VALIDATION ==========
if (empty($email) || empty($password_input)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please enter email and password'
    ]);
    exit;
}

// Email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt_time'] = time();
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid email format'
    ]);
    exit;
}

// ========== CAPTCHA VALIDATION (After 3 failed attempts) ==========
if ($_SESSION['login_attempts'] >= 3) {
    $expected_captcha = $_SESSION['captcha_code'] ?? '';
    if (empty($captcha_input) || $captcha_input !== $expected_captcha) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid CAPTCHA code. Please try again.',
            'requires_captcha' => true
        ]);
        exit;
    }
    // Clear captcha after successful validation
    unset($_SESSION['captcha_code']);
}

// ========== DATABASE CONNECTION ==========
$host = 'localhost';
$dbname = 'u929623538_cibil';
$username = 'u929623538_cibilrepair';
$password = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check users table first
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND status = 'active'");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If not found, check admin_users table
    if (!$user) {
        $stmt2 = $pdo->prepare("SELECT * FROM admin_users WHERE email = :email AND is_active = 1");
        $stmt2->execute([':email' => $email]);
        $user = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        // Map role for admin_users
        if ($user && isset($user['is_active'])) {
            $user['role'] = 'admin';
        }
    }
    
    if (!$user) {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid email or password'
        ]);
        exit;
    }
    
    // Verify password
    if (!password_verify($password_input, $user['password'])) {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        
        $remaining = max(0, 5 - $_SESSION['login_attempts']);
        $requires_captcha = ($_SESSION['login_attempts'] >= 3);
        
        echo json_encode([
            'success' => false, 
            'message' => "Invalid email or password. {$remaining} attempts remaining.",
            'requires_captcha' => $requires_captcha,
            'attempts' => $_SESSION['login_attempts']
        ]);
        exit;
    }
    
    // ========== CHECK IF 2FA IS ENABLED FOR USER ==========
    $twofa_enabled = $user['twofa_enabled'] ?? false;
    $twofa_secret = $user['twofa_secret'] ?? null;
    
    // Generate OTP for 2FA if enabled
    if ($twofa_enabled) {
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $_SESSION['2fa_user_id'] = $user['id'];
        $_SESSION['2fa_email'] = $user['email'];
        $_SESSION['2fa_otp'] = $otp;
        $_SESSION['2fa_expiry'] = time() + 300; // 5 minutes
        
        echo json_encode([
            'success' => true,
            'requires_2fa' => true,
            'message' => '2FA verification required. OTP sent to your registered device.',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'] ?? ($user['full_name'] ?? 'User'),
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
        exit;
    }
    
    // ========== RESET LOGIN ATTEMPTS ON SUCCESS ==========
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
    
    // ========== SET SESSION VARIABLES ==========
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'] ?? ($user['full_name'] ?? 'User');
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['login_ip'] = $ip_address;
    
    // ========== DEVICE RECOGNITION ==========
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $device_info = getDeviceInfo($user_agent);
    $_SESSION['device_info'] = $device_info;
    
    // ========== UPDATE LAST LOGIN ==========
    try {
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW(), last_login_ip = :ip WHERE id = :id");
        $updateStmt->execute([':ip' => $ip_address, ':id' => $user['id']]);
    } catch (Exception $e) {
        // Table might not have column, ignore
    }
    
    // ========== LOG LOGIN HISTORY ==========
    try {
        $historyTable = 'login_history';
        $checkTable = $pdo->query("SHOW TABLES LIKE '$historyTable'");
        if ($checkTable->rowCount() > 0) {
            $logStmt = $pdo->prepare("INSERT INTO login_history (user_id, login_time, ip_address, user_agent, device, browser, success) 
                                       VALUES (:user_id, NOW(), :ip, :ua, :device, :browser, 1)");
            $logStmt->execute([
                ':user_id' => $user['id'],
                ':ip' => $ip_address,
                ':ua' => $user_agent,
                ':device' => $device_info['device'],
                ':browser' => $device_info['browser']
            ]);
        }
    } catch (Exception $e) {
        // Log table might not exist, ignore
    }
    
    // ========== HANDLE REMEMBER ME ==========
    if ($remember_me) {
        $token = bin2hex(random_bytes(32));
        $token_expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Store token in database
        try {
            $tokenStmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expiry)
                                         ON DUPLICATE KEY UPDATE token = :token, expires_at = :expiry");
            $tokenStmt->execute([
                ':user_id' => $user['id'],
                ':token' => password_hash($token, PASSWORD_DEFAULT),
                ':expiry' => $token_expiry
            ]);
        } catch (Exception $e) {
            // Table might not exist, create it
            $pdo->exec("CREATE TABLE IF NOT EXISTS user_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $tokenStmt = $pdo->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expiry)");
            $tokenStmt->execute([
                ':user_id' => $user['id'],
                ':token' => password_hash($token, PASSWORD_DEFAULT),
                ':expiry' => $token_expiry
            ]);
        }
        
        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
    }
    
    // ========== DETERMINE REDIRECT BASED ON ROLE ==========
    // FIXED: Added 'hr' and 'employee' cases
    $redirect = match($user['role']) {
        'admin' => 'admin-dashboard.php',
        'partner' => 'partner-dashboard.php',
        'client' => 'client-dashboard.php',
        'employee' => 'employee-dashboard.php',
        'hr' => 'hr-dashboard.php',
        default => 'client-dashboard.php'
    };
    
    // ========== SUCCESS RESPONSE ==========
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirect,
        'ip_address' => $ip_address,
        'user' => [
            'id' => $user['id'],
            'name' => $_SESSION['user_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error. Please try again later.'
    ]);
}

// ========== HELPER FUNCTION: Get Device Info ==========
function getDeviceInfo($user_agent) {
    $device = 'Unknown';
    $browser = 'Unknown';
    
    if (strpos($user_agent, 'Windows') !== false) $device = 'Windows';
    elseif (strpos($user_agent, 'Mac') !== false) $device = 'Mac';
    elseif (strpos($user_agent, 'Linux') !== false) $device = 'Linux';
    elseif (strpos($user_agent, 'Android') !== false) $device = 'Android';
    elseif (strpos($user_agent, 'iPhone') !== false) $device = 'iOS';
    elseif (strpos($user_agent, 'iPad') !== false) $device = 'iPad';
    
    if (strpos($user_agent, 'Chrome') !== false && strpos($user_agent, 'Edg') === false) $browser = 'Chrome';
    elseif (strpos($user_agent, 'Firefox') !== false) $browser = 'Firefox';
    elseif (strpos($user_agent, 'Safari') !== false && strpos($user_agent, 'Chrome') === false) $browser = 'Safari';
    elseif (strpos($user_agent, 'Edge') !== false) $browser = 'Edge';
    elseif (strpos($user_agent, 'Opera') !== false) $browser = 'Opera';
    
    return ['device' => $device, 'browser' => $browser];
}
?>