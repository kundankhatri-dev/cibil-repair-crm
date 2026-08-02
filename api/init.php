<?php
// ============================================================
// API INITIALIZATION - FOR ALL API ENDPOINTS
// ============================================================

// 1. CRITICAL: Disable error display FIRST
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// 2. Start output buffering to catch any accidental output
ob_start();

// 3. Set JSON header
header('Content-Type: application/json; charset=utf-8');

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================================
// RESPONSE HELPER
// ============================================================

function apiResponse($success, $message = '', $data = null, $code = 200) {
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    header('Content-Type: application/json');
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// ============================================================
// ERROR HANDLER
// ============================================================

function apiErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("API Error: $errstr in $errfile on line $errline");
    apiResponse(false, 'An internal error occurred', null, 500);
    return true;
}
set_error_handler('apiErrorHandler');

// ============================================================
// EXCEPTION HANDLER
// ============================================================

function apiExceptionHandler($exception) {
    error_log("API Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    apiResponse(false, 'An internal error occurred', null, 500);
}
set_exception_handler('apiExceptionHandler');

// ============================================================
// SHUTDOWN HANDLER (Fatal Errors)
// ============================================================

function apiShutdownHandler() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal API Error: {$error['message']} in {$error['file']} on line {$error['line']}");
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'A critical error occurred. Please check server logs.'
        ]);
        exit();
    }
}
register_shutdown_function('apiShutdownHandler');

// ============================================================
// AUTHENTICATION HELPERS
// ============================================================

function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
        apiResponse(false, 'Unauthorized. Please login.', null, 401);
    }
}

function requireAdmin() {
    requireAuth();
    $role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
    if (!in_array($role, ['admin', 'super_admin'])) {
        apiResponse(false, 'Admin access required.', null, 403);
    }
}

// ============================================================
// CSRF VALIDATION
// ============================================================

function validateCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return true;
    }
    
    $headers = getallheaders();
    $token = $headers['X-CSRF-Token'] ?? $headers['X-CSRF-TOKEN'] ?? '';
    
    if (empty($token)) {
        $token = $_POST['csrf_token'] ?? '';
    }
    
    if (empty($token)) {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['csrf_token'] ?? '';
    }
    
    if (empty($token)) {
        error_log("CSRF: No token provided");
        return false;
    }
    
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $valid = hash_equals($sessionToken, $token);
    
    if (!$valid) {
        error_log("CSRF: Token mismatch. Session: " . substr($sessionToken, 0, 20) . "... Received: " . substr($token, 0, 20) . "...");
    }
    
    return $valid;
}

// ============================================================
// DATABASE HELPERS
// ============================================================

function dbFetchAll($sql, $types = '', ...$params) {
    global $conn;
    try {
        if ($types && count($params) > 0) {
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) return [];
            if (!empty($types)) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $rows;
        }
        $result = mysqli_query($conn, $sql);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    } catch (Exception $e) {
        error_log("DB Fetch All Error: " . $e->getMessage());
        return [];
    }
}

function dbFetchOne($sql, $types = '', ...$params) {
    $rows = dbFetchAll($sql, $types, ...$params);
    return $rows[0] ?? null;
}

function dbExecute($sql, $types = '', ...$params) {
    global $conn;
    try {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return -1;
        if ($types && count($params) > 0) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return $affected;
    } catch (Exception $e) {
        error_log("DB Execute Error: " . $e->getMessage());
        return -1;
    }
}

function dbLastId() {
    global $conn;
    return (int)mysqli_insert_id($conn);
}

// ============================================================
// SECURITY HELPERS
// ============================================================

function sanitize($input) {
    if ($input === null) return '';
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^[6-9]\d{9}$/', $phone) === 1;
}

// ============================================================
// INPUT HELPERS
// ============================================================

function getJsonInput() {
    $input = json_decode(file_get_contents('php://input'), true);
    return $input ?? [];
}

// ============================================================
// PAGINATION HELPERS
// ============================================================

function getPaginationParams() {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    if ($limit < 1) $limit = 10;
    if ($limit > 500) $limit = 100;
    if ($offset < 0) $offset = 0;
    return ['limit' => $limit, 'offset' => $offset];
}

function getSearchParams() {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';
    return ['search' => $search, 'status' => $status, 'priority' => $priority];
}

// ============================================================
// ACTIVITY LOGGING
// ============================================================

function logActivity($action, $details = null) {
    global $conn;
    try {
        $user_id = $_SESSION['user_id'] ?? 1;
        $user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $sql = "INSERT INTO activity_logs (user_id, user_name, action, details, ip_address, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issss", $user_id, $user_name, $action, $details, $ip);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    } catch(Exception $e) {
        error_log("Log Activity Error: " . $e->getMessage());
    }
}

// ============================================================
// EMAIL HELPER
// ============================================================

function sendEmail($to, $subject, $body, $isHtml = true) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
    $headers .= "From: CIBIL Repair <contact@cibilrepair.in>\r\n";
    $headers .= "Reply-To: contact@cibilrepair.in\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $body, $headers);
}

// ============================================================
// CLEAN OUTPUT BUFFER
// ============================================================

if (ob_get_length()) ob_clean();

// ============================================================
// IF THIS FILE IS ACCESSED DIRECTLY
// ============================================================

if (basename($_SERVER['PHP_SELF']) === 'init.php') {
    echo json_encode([
        'success' => true, 
        'message' => 'API initialized',
        'timestamp' => date('Y-m-d H:i:s'),
        'csrf_token' => $_SESSION['csrf_token']
    ]);
    exit;
}

// If included by another file, don't output anything
// Let the including file handle the response
?>