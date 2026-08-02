<?php
// ============================================================
// CIBIL REPAIR CRM - API Configuration
// ============================================================

// ===== ERROR REPORTING (Production) =====
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ===== TIMEZONE =====
date_default_timezone_set('Asia/Kolkata');

// ============================================================
// DATABASE CONFIGURATION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

// MySQLi Connection
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die(json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]));
}

mysqli_set_charset($conn, 'utf8mb4');

// PDO Connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die(json_encode(['error' => 'PDO Connection failed: ' . $e->getMessage()]));
}

// ============================================================
// SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// AI API CONFIGURATION
// ============================================================

define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');

// Load API key from file
$keyFile = __DIR__ . '/.api_key';
if (file_exists($keyFile)) {
    $apiKey = trim(file_get_contents($keyFile));
    define('OPENAI_API_KEY', $apiKey);
} else {
    // Use environment variable or fallback
    $apiKey = getenv('OPENAI_API_KEY') ?: '';
    define('OPENAI_API_KEY', $apiKey);
}

// ============================================================
// APPLICATION SETTINGS
// ============================================================

define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['txt', 'pdf', 'doc', 'docx', 'csv', 'json']);
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Create uploads directory
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ============================================================
// EMAIL CONFIGURATION
// ============================================================

// SMTP Configuration (for PHPMailer if available)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');

// Default sender
define('EMAIL_FROM', 'noreply@cibilrepair.in');
define('EMAIL_FROM_NAME', 'CIBIL Repair');
define('EMAIL_REPLY_TO', 'support@cibilrepair.in');

// ============================================================
// CORS HEADERS
// ============================================================

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
}

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Send success response
 */
function sendSuccess($data = null, $message = 'Success') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit();
}

/**
 * Send error response
 */
function sendError($message = 'An error occurred', $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit();
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? 1;
}

/**
 * Log activity
 */
function logActivity($action, $details = null) {
    global $conn;
    try {
        $user_id = getCurrentUserId();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isss', $user_id, $action, $details, $ip);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } catch(Exception $e) {
        // Silently fail
    }
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone
 */
function validatePhone($phone) {
    return preg_match('/^[6-9]\d{9}$/', $phone) === 1;
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Send email using PHPMailer if available, fallback to mail()
 */
function sendEmail($to, $subject, $message, $from = null, $fromName = null) {
    // Try PHPMailer first if available
    $phpmailerPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($phpmailerPath)) {
        try {
            require_once $phpmailerPath;
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = !empty(SMTP_USER) && !empty(SMTP_PASS);
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            
            $mail->setFrom($from ?: EMAIL_FROM, $fromName ?: EMAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->addReplyTo(EMAIL_REPLY_TO);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>', '<p>'], ["\n", "\n", ''], $message));
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $e->getMessage());
            // Fall through to mail()
        }
    }
    
    // Fallback to mail() function
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . ($fromName ?: EMAIL_FROM_NAME) . " <" . ($from ?: EMAIL_FROM) . ">" . "\r\n";
    $headers .= "Reply-To: " . EMAIL_REPLY_TO . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Get user by reset token
 */
function getUserByResetToken($token, $email) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT id, name, email FROM users WHERE email = ? AND reset_token = ? AND reset_expiry > NOW()");
    mysqli_stmt_bind_param($stmt, "ss", $email, $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $user;
}

/**
 * Clear reset token
 */
function clearResetToken($userId) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE users SET reset_token = NULL, reset_expiry = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return true;
}
?>