<?php
require_once '../config.php';
require_once 'config.php';

if (isset($_GET['code'])) {
    $token_url = 'https://oauth2.googleapis.com/token';
    $data = [
        'code' => $_GET['code'],
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'redirect_uri' => $google_redirect_uri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($response, true);
    $access_token = $token_data['access_token'];
    
    // Get user info
    $user_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $access_token;
    $ch = curl_init($user_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $user_response = curl_exec($ch);
    curl_close($ch);
    $google_user = json_decode($user_response, true);
    
    if ($google_user && isset($google_user['email'])) {
        $email = $google_user['email'];
        $name = $google_user['name'];
        $picture = $google_user['picture'];
        
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

header('Location: https://cibilrepair.in/login.html');
?>