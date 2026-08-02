<?php
// config/database.php
// SECURITY: Prevent direct access to this file
if (basename($_SERVER['PHP_SELF']) == 'database.php') {
    header('HTTP/1.0 403 Forbidden');
    die('Access denied. This file cannot be accessed directly.');
}

session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'u929623538_cibil');
define('DB_USER', 'u929623538_cibilrepair');
define('DB_PASS', 'Kundanlaxmi@1995'); // WARNING: Move this outside public_html for production

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch(PDOException $e) {
            // Log error instead of showing to users
            error_log('Database connection failed: ' . $e->getMessage());
            die(json_encode(['success' => false, 'error' => 'Database connection failed. Please try again later.']));
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Helper method to execute queries safely
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log('Query failed: ' . $e->getMessage());
            return false;
        }
    }
    
    // Helper method to get single row
    public function fetchOne($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    // Helper method to get all rows
    public function fetchAll($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Helper method to insert and return last insert ID
    public function insert($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $this->connection->lastInsertId() : false;
    }
}

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true &&
           isset($_SESSION['last_activity']) && 
           (time() - $_SESSION['last_activity']) < 3600; // 1 hour timeout
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.html');
        exit();
    }
    // Update last activity
    $_SESSION['last_activity'] = time();
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirectIfNotAdmin() {
    if (!isAdmin()) {
        header('HTTP/1.0 403 Forbidden');
        die(json_encode(['success' => false, 'error' => 'Admin access required']));
    }
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// CSRF Protection Functions
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting helper
function checkRateLimit($key, $limit = 10, $timeWindow = 60) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $rateKey = "rate_limit_{$key}_{$ip}";
    
    if (!isset($_SESSION[$rateKey])) {
        $_SESSION[$rateKey] = ['count' => 1, 'first_request' => time()];
        return true;
    }
    
    $data = $_SESSION[$rateKey];
    
    if (time() - $data['first_request'] > $timeWindow) {
        // Reset window
        $_SESSION[$rateKey] = ['count' => 1, 'first_request' => time()];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        return false;
    }
    
    $_SESSION[$rateKey]['count']++;
    return true;
}

// Log user activity
function logActivity($userId, $action, $details = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO user_activity_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch(PDOException $e) {
        error_log('Activity logging failed: ' . $e->getMessage());
    }
}

// ============================================
// INITIALIZE SESSION SECURITY
// ============================================

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // Every 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Set secure session cookie parameters
if (session_status() === PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}
?>