<?php
require_once '../config.php';
session_start();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Make sure $conn is defined in config.php
global $conn;

// Generate secret
function generateSecret() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < 16; $i++) {
        $secret .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $secret;
}

// Simple TOTP verification (fixed version)
function verifyTOTP($secret, $code) {
    // For production, use: https://github.com/Spomky-Labs/otphp
    // This is a simplified check
    $expected_codes = [];
    for ($i = -1; $i <= 1; $i++) {
        $time = floor(time() / 30) + $i;
        // Simple check - in production use proper TOTP library
        $expected_codes[] = substr(md5($secret . $time), 0, 6);
    }
    return in_array($code, $expected_codes);
}

function getQRCodeUrl($secret, $email) {
    $issuer = 'CIBIL Repair';
    $label = $issuer . ':' . $email;
    return "otpauth://totp/$label?secret=$secret&issuer=$issuer";
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'setup') {
    // Check if already has 2FA
    $check = mysqli_query($conn, "SELECT * FROM user_2fa WHERE user_id = $user_id");
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        if ($row['is_enabled']) {
            echo json_encode(['success' => false, 'error' => '2FA already enabled']);
            exit;
        }
        $secret = $row['secret'];
    } else {
        $secret = generateSecret();
        $insert = mysqli_prepare($conn, "INSERT INTO user_2fa (user_id, secret) VALUES (?, ?)");
        mysqli_stmt_bind_param($insert, "is", $user_id, $secret);
        mysqli_stmt_execute($insert);
    }
    
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email FROM users WHERE id = $user_id"));
    $qr_url = getQRCodeUrl($secret, $user['email']);
    
    echo json_encode([
        'success' => true,
        'secret' => $secret,
        'qr_url' => $qr_url
    ]);
}

elseif ($action === 'verify') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = $input['code'] ?? '';
    
    $result = mysqli_query($conn, "SELECT secret FROM user_2fa WHERE user_id = $user_id");
    $row = mysqli_fetch_assoc($result);
    
    if (verifyTOTP($row['secret'], $code)) {
        $update = mysqli_prepare($conn, "UPDATE user_2fa SET is_enabled = 1 WHERE user_id = ?");
        mysqli_stmt_bind_param($update, "i", $user_id);
        mysqli_stmt_execute($update);
        echo json_encode(['success' => true, 'message' => '2FA enabled successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid code']);
    }
}

elseif ($action === 'disable') {
    $input = json_decode(file_get_contents('php://input'), true);
    $password = $input['password'] ?? '';
    
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM users WHERE id = $user_id"));
    if (password_verify($password, $user['password'])) {
        $update = mysqli_prepare($conn, "UPDATE user_2fa SET is_enabled = 0 WHERE user_id = ?");
        mysqli_stmt_bind_param($update, "i", $user_id);
        mysqli_stmt_execute($update);
        echo json_encode(['success' => true, 'message' => '2FA disabled']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid password']);
    }
}

else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>