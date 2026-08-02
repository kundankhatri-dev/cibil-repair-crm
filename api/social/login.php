<?php
require_once '../config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$provider = $input['provider'] ?? '';
$access_token = $input['access_token'] ?? '';
$user_data = $input['user_data'] ?? [];

if ($provider === 'google') {
    // Verify Google token
    $verify_url = "https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=" . $access_token;
    $ch = curl_init($verify_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $google_user = json_decode($response, true);
    
    if ($google_user && isset($google_user['email'])) {
        $email = $google_user['email'];
        $name = $google_user['name'];
        $picture = $google_user['picture'];
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid Google token']);
        exit;
    }
} elseif ($provider === 'facebook') {
    // Verify Facebook token
    $verify_url = "https://graph.facebook.com/me?access_token={$access_token}&fields=id,name,email,picture";
    $ch = curl_init($verify_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $fb_user = json_decode($response, true);
    
    if ($fb_user && isset($fb_user['email'])) {
        $email = $fb_user['email'];
        $name = $fb_user['name'];
        $picture = $fb_user['picture']['data']['url'] ?? '';
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid Facebook token']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid provider']);
    exit;
}

// Check if user exists
$query = mysqli_prepare($conn, "SELECT id, name, email, role, status FROM users WHERE email = ?");
mysqli_stmt_bind_param($query, "s", $email);
mysqli_stmt_execute($query);
$result = mysqli_stmt_get_result($query);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($query);

if (!$user) {
    // Create new user
    $hashed_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $insert = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'client', 'active', NOW())");
    mysqli_stmt_bind_param($insert, "sss", $name, $email, $hashed_password);
    mysqli_stmt_execute($insert);
    $user_id = mysqli_insert_id($conn);
    mysqli_stmt_close($insert);
    
    $user = ['id' => $user_id, 'name' => $name, 'email' => $email, 'role' => 'client', 'status' => 'active'];
}

if ($user['status'] != 'active') {
    echo json_encode(['success' => false, 'error' => 'Account not active']);
    exit;
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = $user['role'];

echo json_encode([
    'success' => true,
    'user' => $user,
    'redirect' => $user['role'] === 'admin' ? 'admin-dashboard.html' : ($user['role'] === 'partner' ? 'partner-dashboard.html' : 'client-dashboard.html')
]);

mysqli_close($conn);
?>