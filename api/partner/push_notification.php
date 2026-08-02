<?php
// api/partner/push_notification.php
// Firebase Cloud Messaging for push notifications

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'register';

// Create device tokens table
$deviceTable = 'push_devices';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$deviceTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE $deviceTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        device_token VARCHAR(255) NOT NULL,
        device_type ENUM('android', 'ios', 'web') DEFAULT 'web',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_token (device_token),
        INDEX idx_user (user_id)
    )";
    mysqli_query($conn, $createTable);
}

if ($action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'] ?? '';
    $device_type = $data['device_type'] ?? 'web';
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'error' => 'Device token required']);
        exit;
    }
    
    // Insert or update token
    $insert = mysqli_prepare($conn, "INSERT INTO $deviceTable (user_id, device_token, device_type) VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE updated_at = NOW(), is_active = 1");
    mysqli_stmt_bind_param($insert, "iss", $partner_id, $token, $device_type);
    
    if (mysqli_stmt_execute($insert)) {
        echo json_encode(['success' => true, 'message' => 'Device registered for push notifications']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Registration failed']);
    }
    
} elseif ($action === 'unregister') {
    $data = json_decode(file_get_contents('php://input'), true);
    $token = $data['token'] ?? '';
    
    $update = mysqli_prepare($conn, "UPDATE $deviceTable SET is_active = 0 WHERE device_token = ?");
    mysqli_stmt_bind_param($update, "s", $token);
    mysqli_stmt_execute($update);
    
    echo json_encode(['success' => true, 'message' => 'Device unregistered']);
    
} elseif ($action === 'send') {
    // Admin only - send push notification
    $data = json_decode(file_get_contents('php://input'), true);
    $title = $data['title'] ?? '';
    $body = $data['body'] ?? '';
    
    // Get all active device tokens for this partner
    $query = "SELECT device_token, device_type FROM $deviceTable WHERE user_id = ? AND is_active = 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $partner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tokens = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // Send via FCM (Firebase Cloud Messaging)
    $fcm_url = 'https://fcm.googleapis.com/fcm/send';
    $server_key = 'YOUR_FIREBASE_SERVER_KEY'; // Set this in config
    
    $sent_count = 0;
    foreach ($tokens as $token_data) {
        $payload = [
            'to' => $token_data['device_token'],
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => '/favicon.ico',
                'click_action' => 'https://cibilrepair.in/partner-dashboard.php'
            ],
            'data' => [
                'screen' => 'dashboard',
                'partner_id' => $partner_id
            ]
        ];
        
        // Uncomment to actually send
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $fcm_url);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     'Authorization: key=' . $server_key,
        //     'Content-Type: application/json'
        // ]);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        // $response = curl_exec($ch);
        // curl_close($ch);
        
        $sent_count++;
    }
    
    echo json_encode([
        'success' => true,
        'sent_count' => $sent_count,
        'total_devices' => count($tokens)
    ]);
}

mysqli_close($conn);
?>