<?php
// ============================================================
// CIBIL REPAIR CRM - Database Helper (API-SAFE VERSION)
// ============================================================

// ===== CRITICAL: Disable error display for API =====
ini_set('display_errors', 0);  // CHANGED from 1 to 0
error_reporting(E_ALL);        // Keep logging errors, but don't display
ini_set('log_errors', 1);      // Ensure errors are logged

// ===== Start output buffering to catch any stray output =====
ob_start();

// ===== Prevent direct access =====
if (basename($_SERVER['PHP_SELF']) === 'db.php') {
    http_response_code(403);
    ob_clean();
    echo json_encode(['error' => 'Direct access forbidden.']);
    exit();
}

// ===== Session handling =====
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// ===== Database configuration =====
require_once __DIR__ . '/config.php';

// ===== Connect with error suppression =====
$conn = getMysqli();

if (!$conn) {
    error_log('DB connection failed: ' . mysqli_connect_error());
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'error' => 'Database connection failed. Please check server configuration.'
    ]);
    exit();
}

mysqli_set_charset($conn, 'utf8mb4');

// ===== Helper Functions =====

/**
 * Validate CSRF token
 */
function validateCSRF(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') return true;
    
    $headers = getallheaders();
    $token = $headers['X-CSRF-Token'] ?? $headers['X-CSRF-TOKEN'] ?? '';
    
    if (empty($token)) {
        $token = $_POST['csrf_token'] ?? '';
    }
    
    if (empty($token)) {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['csrf_token'] ?? '';
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return true;
    }
    
    return !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require authentication
 */
function requireAuth(): void {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
        jsonResponse(false, 'Unauthorized. Please login.', null, 401);
    }
}

/**
 * Check if user is admin
 */
function isAdmin(): bool {
    $role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
    return in_array($role, ['admin', 'super_admin']);
}

/**
 * Require admin access
 */
function requireAdmin(): void {
    requireAuth();
    if (!isAdmin()) {
        jsonResponse(false, 'Admin access required.', null, 403);
    }
}

/**
 * Send JSON response (cleans any buffered output)
 */
function jsonResponse(bool $success, string $message = '', $data = null, int $httpCode = 200): void {
    // Clean any output buffered before this
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($httpCode);
    header('Content-Type: application/json');
    
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    
    if ($data !== null) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (!isset($response[$key])) {
                    $response[$key] = $value;
                }
            }
        } else {
            $response['data'] = $data;
        }
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

/**
 * Log activity
 */
function logActivity($conn, string $action, string $details = '', ?string $userName = null): void {
    $userName = $userName ?? ($_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $sql = "INSERT INTO activity_logs (user_name, action, details, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssss', $userName, $action, $details, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/**
 * Database query with prepared statements
 */
function dbQuery($conn, string $sql, string $types = '', ...$params): ?mysqli_result {
    if ($types && count($params) > 0) {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return null;
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        return $result ?: null;
    }
    $result = mysqli_query($conn, $sql);
    return ($result instanceof mysqli_result) ? $result : null;
}

/**
 * Fetch all rows
 */
function dbFetchAll($conn, string $sql, string $types = '', ...$params): array {
    $result = dbQuery($conn, $sql, $types, ...$params);
    if (!$result) return [];
    
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);
    return $rows;
}

/**
 * Fetch one row
 */
function dbFetchOne($conn, string $sql, string $types = '', ...$params): ?array {
    $result = dbQuery($conn, $sql, $types, ...$params);
    if (!$result) return null;
    
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    return $row ?: null;
}

/**
 * Execute query (INSERT, UPDATE, DELETE)
 */
function dbExecute($conn, string $sql, string $types = '', ...$params): int {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return -1;
    
    if ($types && count($params) > 0) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $affected;
}

/**
 * Get last inserted ID
 */
function dbLastId($conn): int {
    return (int)mysqli_insert_id($conn);
}

// ===== Clear any initial buffered output =====
if (ob_get_level() && ob_get_length()) {
    ob_clean();
}
?>