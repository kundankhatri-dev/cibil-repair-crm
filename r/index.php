<?php
// ============================================================
// Short Link Redirect: /r/CODE -> /?ref=CODE
// ============================================================

// Get the full URL path
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Extract referral code from /r/CODE
// Example: /r/PART-D3D94468 -> PART-D3D94468
$parts = explode('/', trim($path, '/'));
$referral_code = $parts[1] ?? '';

// If a referral code is found, redirect to homepage with ref parameter
if (!empty($referral_code) && preg_match('/^[A-Za-z0-9-]+$/', $referral_code)) {
    $redirect_url = '/?ref=' . urlencode($referral_code);
    header("Location: $redirect_url");
    exit;
}

// If no valid code, redirect to homepage
header("Location: /");
exit;
?>