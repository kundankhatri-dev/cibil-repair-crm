<?php
// api/partner/get_notification_settings.php
// Partner Get Notification Settings API - Retrieve notification preferences

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

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner and get contact info
$role_check = mysqli_prepare($conn, "SELECT role, name, email, phone FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// ========== ENSURE SETTINGS TABLE EXISTS ==========
$settingsTable = 'partner_notification_settings';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$settingsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS $settingsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL UNIQUE,
        email_notifications TINYINT(1) DEFAULT 1,
        sms_notifications TINYINT(1) DEFAULT 0,
        whatsapp_notifications TINYINT(1) DEFAULT 0,
        lead_assigned_notify TINYINT(1) DEFAULT 1,
        lead_converted_notify TINYINT(1) DEFAULT 1,
        lead_followup_reminder TINYINT(1) DEFAULT 1,
        payout_notify TINYINT(1) DEFAULT 1,
        weekly_report TINYINT(1) DEFAULT 1,
        monthly_report TINYINT(1) DEFAULT 1,
        marketing_emails TINYINT(1) DEFAULT 0,
        notification_sound TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createTable);
}

// ========== GET OR CREATE SETTINGS ==========
$query = "SELECT 
            id,
            email_notifications,
            sms_notifications,
            whatsapp_notifications,
            lead_assigned_notify,
            lead_converted_notify,
            lead_followup_reminder,
            payout_notify,
            weekly_report,
            monthly_report,
            marketing_emails,
            notification_sound
          FROM $settingsTable 
          WHERE partner_id = ?";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$settings = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// If no settings exist, create default
if (!$settings) {
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO $settingsTable 
        (partner_id, email_notifications, sms_notifications, whatsapp_notifications, 
         lead_assigned_notify, lead_converted_notify, lead_followup_reminder,
         payout_notify, weekly_report, monthly_report, marketing_emails, notification_sound) 
        VALUES (?, 1, 0, 0, 1, 1, 1, 1, 1, 1, 0, 1)");
    
    if ($insert_stmt) {
        mysqli_stmt_bind_param($insert_stmt, "i", $partner_id);
        mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
    }
    
    // Set default settings
    $settings = [
        'id' => null,
        'email_notifications' => 1,
        'sms_notifications' => 0,
        'whatsapp_notifications' => 0,
        'lead_assigned_notify' => 1,
        'lead_converted_notify' => 1,
        'lead_followup_reminder' => 1,
        'payout_notify' => 1,
        'weekly_report' => 1,
        'monthly_report' => 1,
        'marketing_emails' => 0,
        'notification_sound' => 1
    ];
}

// Convert to boolean for frontend
$settings_boolean = [];
foreach ($settings as $key => $value) {
    if ($key !== 'id') {
        $settings_boolean[$key] = (bool)$value;
    } else {
        $settings_boolean[$key] = $value;
    }
}

// Get recent notification history (last 5 notifications)
$notifications = [];
$checkNotificationsTable = mysqli_query($conn, "SHOW TABLES LIKE 'partner_notifications'");
if (mysqli_num_rows($checkNotificationsTable) > 0) {
    $notif_query = "SELECT 
                        id,
                        title,
                        message,
                        is_read,
                        DATE_FORMAT(created_at, '%d-%m-%Y %h:%i %p') as created_at,
                        DATE_FORMAT(created_at, '%Y-%m-%d') as created_date
                    FROM partner_notifications 
                    WHERE partner_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 5";
    
    $notif_stmt = mysqli_prepare($conn, $notif_query);
    mysqli_stmt_bind_param($notif_stmt, "i", $partner_id);
    mysqli_stmt_execute($notif_stmt);
    $notif_result = mysqli_stmt_get_result($notif_stmt);
    $notifications = mysqli_fetch_all($notif_result, MYSQLI_ASSOC);
    mysqli_stmt_close($notif_stmt);
    
    // Count unread notifications
    $unread_query = "SELECT COUNT(*) as unread FROM partner_notifications WHERE partner_id = ? AND is_read = 0";
    $unread_stmt = mysqli_prepare($conn, $unread_query);
    mysqli_stmt_bind_param($unread_stmt, "i", $partner_id);
    mysqli_stmt_execute($unread_stmt);
    $unread_result = mysqli_stmt_get_result($unread_stmt);
    $unread_data = mysqli_fetch_assoc($unread_result);
    $unread_count = $unread_data['unread'] ?? 0;
    mysqli_stmt_close($unread_stmt);
}

// Get last notification sent timestamp
$last_notification = null;
if (isset($_SESSION['last_notification_check'])) {
    $last_notification = $_SESSION['last_notification_check'];
}
$_SESSION['last_notification_check'] = date('Y-m-d H:i:s');

// ========== GET DELIVERY CHANNELS ==========
$delivery_channels = [
    'email' => [
        'enabled' => (bool)$role_data['email'],
        'value' => $role_data['email'],
        'verified' => true
    ],
    'sms' => [
        'enabled' => (bool)$role_data['phone'],
        'value' => $role_data['phone'],
        'verified' => false
    ],
    'whatsapp' => [
        'enabled' => (bool)$role_data['phone'],
        'value' => $role_data['phone'],
        'verified' => false
    ]
];

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'settings' => $settings_boolean,
    'contact_info' => [
        'email' => $role_data['email'],
        'phone' => $role_data['phone'] ?? null,
        'name' => $role_data['name']
    ],
    'delivery_channels' => $delivery_channels,
    'recent_notifications' => $notifications,
    'unread_count' => $unread_count ?? 0,
    'last_check' => $last_notification,
    'has_email' => !empty($role_data['email']),
    'has_phone' => !empty($role_data['phone'])
]);

mysqli_close($conn);
?>