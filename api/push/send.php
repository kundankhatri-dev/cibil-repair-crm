<?php
require_once '../config.php';

function sendPushNotification($user_id, $title, $body, $url = '/') {
    global $conn;
    
    // Get user's subscriptions
    $query = "SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $subscriptions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
    $vapid_public_key = 'YOUR_VAPID_PUBLIC_KEY';
    $vapid_private_key = 'YOUR_VAPID_PRIVATE_KEY';
    
    $success = false;
    
    foreach ($subscriptions as $sub) {
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/icon-192x192.png',
            'url' => $url,
            'badge' => '/badge-72x72.png'
        ]);
        
        // Send to Web Push endpoint
        $ch = curl_init($sub['endpoint']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Encoding: aes128gcm',
            'TTL: 86400'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 201) {
            $success = true;
        }
    }
    
    return $success;
}

function queuePushNotification($user_id, $title, $body, $url = '/') {
    global $conn;
    $stmt = mysqli_prepare($conn, "INSERT INTO push_notifications_queue (user_id, title, body, url) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $title, $body, $url);
    return mysqli_stmt_execute($stmt);
}
?>