<?php
require_once '../config.php';

// Facebook OAuth Configuration
$facebook_app_id = 'YOUR_FACEBOOK_APP_ID';
$facebook_app_secret = 'YOUR_FACEBOOK_APP_SECRET';
$facebook_redirect_uri = 'https://cibilrepair.in/api/social/facebook-callback.php';

if (isset($_GET['code'])) {
    $token_url = 'https://graph.facebook.com/v18.0/oauth/access_token';
    $data = [
        'client_id' => $facebook_app_id,
        'client_secret' => $facebook_app_secret,
        'redirect_uri' => $facebook_redirect_uri,
        'code' => $_GET['code']
    ];
    
    $ch = curl_init($token_url . '?' . http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($response, true);
    $access_token = $token_data['access_token'] ?? '';
    
    if ($access_token) {
        $user_url = 'https://graph.facebook.com/me?fields=id,name,email&access_token=' . $access_token;
        $ch = curl_init($user_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $user_response = curl_exec($ch);
        curl_close($ch);
        $fb_user = json_decode($user_response, true);
        
        if ($fb_user && isset($fb_user['email'])) {
            $email = $fb_user['email'];
            $name = $fb_user['name'];
            
            // Check if user exists
            $query = mysqli_prepare($conn, "SELECT id, name, email, role, status FROM users WHERE email = ?");
            mysqli_stmt_bind_param($query, "s", $email);
            mysqli_stmt_execute($query);
            $result = mysqli_stmt_get_result($query);
            $user = mysqli_fetch_assoc($result);
            
            if (!$user) {
                $hashed_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $insert = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'client', 'active', NOW())");
                mysqli_stmt_bind_param($insert, "sss", $name, $email, $hashed_password);
                mysqli_stmt_execute($insert);
                $user_id = mysqli_insert_id($conn);
                $user = ['id' => $user_id, 'name' => $name, 'email' => $email, 'role' => 'client', 'status' => 'active'];
            }
            
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            
            header('Location: https://cibilrepair.in/client-dashboard.html');
            exit();
        }
    }
}

header('Location: https://cibilrepair.in/login.html?error=facebook_login_failed');
exit();
?>