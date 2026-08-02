<?php
// ============================================================
// LOGOUT — shared by admin, partner, and client dashboards
// ============================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// Clear session data
$_SESSION = [];

// Remove the session cookie itself
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Also clear "remember me" cookie set by login.php
if (isset($_COOKIE['remember_email'])) {
    setcookie('remember_email', '', time() - 42000, '/');
}

session_destroy();

header('Location: login.php');
exit;
