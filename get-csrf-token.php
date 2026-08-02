<?php
// get-csrf-token.php - Generate and return CSRF token
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Also generate captcha if needed (based on login attempts)
$requires_captcha = false;
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3) {
    $requires_captcha = true;
    if (empty($_SESSION['captcha_code'])) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
        $captcha = '';
        for ($i = 0; $i < 6; $i++) {
            $captcha .= $chars[rand(0, strlen($chars) - 1)];
        }
        $_SESSION['captcha_code'] = $captcha;
    }
}

echo json_encode([
    'success' => true,
    'csrf_token' => $_SESSION['csrf_token'],
    'requires_captcha' => $requires_captcha,
    'captcha_code' => $requires_captcha ? ($_SESSION['captcha_code'] ?? '') : null
]);
?>