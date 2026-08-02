<?php
// api/partner/update_notification_settings.php
// Partner Update Notification Settings API - Update notification preferences

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

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role, name FROM users WHERE id = ?");
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

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);

// Helper function to safely get boolean value
function getBoolValue($data, $key, $default = 0) {
    return isset($data[$key]) ? (int)(bool)$data[$key] : $default;
}

$settings = [
    'email_notifications' => getBoolValue($data, 'email_notifications', 1),
    'sms_notifications' => getBoolValue($data, 'sms_notifications', 0),
    'whatsapp_notifications' => getBoolValue($data, 'whatsapp_notifications', 0),
    'lead_assigned_notify' => getBoolValue($data, 'lead_assigned_notify', 1),
    'lead_converted_notify' => getBoolValue($data, 'lead_converted_notify', 1),
    'lead_followup_reminder' => getBoolValue($data, 'lead_followup_reminder', 1),
    'payout_notify' => getBoolValue($data, 'payout_notify', 1),
    'weekly_report' => getBoolValue($data, 'weekly_report', 1),
    'monthly_report' => getBoolValue($data, 'monthly_report', 1),
    'marketing_emails' => getBoolValue($data, 'marketing_emails', 0),
    'notification_sound' => getBoolValue($data, 'notification_sound', 1)
];

// ========== VALIDATE SETTINGS ==========
foreach ($settings as $key => $value) {
    if (!in_array($value, [0, 1])) {
        echo json_encode(['success' => false, 'error' => "Invalid value for $key. Must be 0 or 1"]);
        exit;
    }
}

// ========== CHECK IF SETTINGS EXIST ==========
$check_stmt = mysqli_prepare($conn, "SELECT id FROM $settingsTable WHERE partner_id = ?");
mysqli_stmt_bind_param($check_stmt, "i", $partner_id);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);
$exists = mysqli_stmt_num_rows($check_stmt) > 0;
mysqli_stmt_close($check_stmt);

// ========== GET OLD SETTINGS FOR COMPARISON ==========
$old_settings = [];
if ($exists) {
    $old_query = "SELECT * FROM $settingsTable WHERE partner_id = ?";
    $old_stmt = mysqli_prepare($conn, $old_query);
    mysqli_stmt_bind_param($old_stmt, "i", $partner_id);
    mysqli_stmt_execute($old_stmt);
    $old_result = mysqli_stmt_get_result($old_stmt);
    $old_settings = mysqli_fetch_assoc($old_result);
    mysqli_stmt_close($old_stmt);
}

// ========== UPDATE OR INSERT SETTINGS ==========
if ($exists) {
    $query = "UPDATE $settingsTable SET 
                email_notifications = ?,
                sms_notifications = ?,
                whatsapp_notifications = ?,
                lead_assigned_notify = ?,
                lead_converted_notify = ?,
                lead_followup_reminder = ?,
                payout_notify = ?,
                weekly_report = ?,
                monthly_report = ?,
                marketing_emails = ?,
                notification_sound = ?,
                updated_at = NOW()
              WHERE partner_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiiiiiiiiiii", 
        $settings['email_notifications'],
        $settings['sms_notifications'],
        $settings['whatsapp_notifications'],
        $settings['lead_assigned_notify'],
        $settings['lead_converted_notify'],
        $settings['lead_followup_reminder'],
        $settings['payout_notify'],
        $settings['weekly_report'],
        $settings['monthly_report'],
        $settings['marketing_emails'],
        $settings['notification_sound'],
        $partner_id
    );
} else {
    $query = "INSERT INTO $settingsTable 
                (partner_id, email_notifications, sms_notifications, whatsapp_notifications, 
                 lead_assigned_notify, lead_converted_notify, lead_followup_reminder,
                 payout_notify, weekly_report, monthly_report, marketing_emails, notification_sound) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iiiiiiiiiiii", 
        $partner_id,
        $settings['email_notifications'],
        $settings['sms_notifications'],
        $settings['whatsapp_notifications'],
        $settings['lead_assigned_notify'],
        $settings['lead_converted_notify'],
        $settings['lead_followup_reminder'],
        $settings['payout_notify'],
        $settings['weekly_report'],
        $settings['monthly_report'],
        $settings['marketing_emails'],
        $settings['notification_sound']
    );
}

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

if (mysqli_stmt_execute($stmt)) {
    // Determine what changed for better logging
    $changes = [];
    if ($old_settings) {
        $field_labels = [
            'email_notifications' => 'Email notifications',
            'sms_notifications' => 'SMS notifications',
            'whatsapp_notifications' => 'WhatsApp notifications',
            'lead_assigned_notify' => 'Lead assigned notifications',
            'lead_converted_notify' => 'Lead converted notifications',
            'lead_followup_reminder' => 'Follow-up reminders',
            'payout_notify' => 'Payout notifications',
            'weekly_report' => 'Weekly reports',
            'monthly_report' => 'Monthly reports',
            'marketing_emails' => 'Marketing emails',
            'notification_sound' => 'Notification sound'
        ];
        
        foreach ($settings as $key => $value) {
            $old_value = $old_settings[$key] ?? null;
            if ($old_value !== null && $old_value != $value) {
                $changes[] = $field_labels[$key] . ': ' . ($value ? 'Enabled' : 'Disabled');
            }
        }
    }
    
    // Log activity
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'update_notification_settings', ?, NOW())");
        if ($log_stmt) {
            $description = "Updated notification preferences";
            if (!empty($changes)) {
                $description .= ": " . implode(', ', $changes);
            }
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification settings updated successfully',
        'settings' => $settings,
        'changes_made' => $changes,
        'changes_count' => count($changes)
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update settings: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>