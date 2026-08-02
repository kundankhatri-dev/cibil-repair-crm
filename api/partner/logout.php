<?php
// api/partner/logout.php
// Partner Logout API - Terminate user session

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

// ========== LOG ACTIVITY ==========
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Log logout activity
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'logout', ?, NOW())");
        if ($log_stmt) {
            $description = "User logged out";
            mysqli_stmt_bind_param($log_stmt, "is", $user_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
    // ========== CLEAR REMEMBER ME TOKEN ==========
    // Delete token from database
    $checkTokenTable = mysqli_query($conn, "SHOW TABLES LIKE 'user_tokens'");
    if (mysqli_num_rows($checkTokenTable) > 0) {
        $deleteToken = mysqli_prepare($conn, "DELETE FROM user_tokens WHERE user_id = ?");
        if ($deleteToken) {
            mysqli_stmt_bind_param($deleteToken, "i", $user_id);
            mysqli_stmt_execute($deleteToken);
            mysqli_stmt_close($deleteToken);
        }
    }
    
    // Clear remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
}

// ========== DESTROY SESSION ==========
// Unset all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// ========== CLEAR ANY ADDITIONAL COOKIES ==========
if (isset($_COOKIE['partner_session'])) {
    setcookie('partner_session', '', time() - 3600, '/');
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully',
    'redirect' => 'login.html'
]);

mysqli_close($conn);
?>