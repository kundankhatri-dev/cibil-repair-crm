<?php
// api/partner/get_settings.php
// Partner Get Settings API - Retrieve partner settings and preferences

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

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
$role_check = mysqli_prepare($conn, "SELECT role, name, email, phone, city FROM users WHERE id = ?");
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

// ========== ENSURE PARTNERS TABLE EXISTS ==========
$partnersTable = 'partners';
$checkPartnersTable = mysqli_query($conn, "SHOW TABLES LIKE '$partnersTable'");
if (mysqli_num_rows($checkPartnersTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS $partnersTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        company_name VARCHAR(255),
        bank_name VARCHAR(100),
        account_number VARCHAR(20),
        ifsc_code VARCHAR(20),
        account_holder VARCHAR(100),
        commission_rate DECIMAL(5,2) DEFAULT 10.00,
        total_leads INT DEFAULT 0,
        total_converted INT DEFAULT 0,
        total_commission DECIMAL(12,2) DEFAULT 0,
        pending_payout DECIMAL(12,2) DEFAULT 0,
        referral_code VARCHAR(50) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createTable);
}

// ========== GET PARTNER SETTINGS ==========
$query = "SELECT 
            p.id as partner_id,
            p.commission_rate,
            p.total_leads,
            p.total_converted,
            p.total_commission,
            p.pending_payout,
            p.company_name,
            p.bank_name,
            p.account_number,
            p.ifsc_code,
            p.account_holder,
            p.referral_code,
            u.name,
            u.email,
            u.phone,
            u.city,
            u.status as account_status,
            DATE_FORMAT(u.created_at, '%d-%m-%Y') as member_since
          FROM $partnersTable p
          INNER JOIN users u ON p.user_id = u.id
          WHERE p.user_id = ?";

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

if (!$settings) {
    // Create partner record if not exists
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO $partnersTable (user_id, commission_rate) VALUES (?, 10.00)");
    mysqli_stmt_bind_param($insert_stmt, "i", $partner_id);
    mysqli_stmt_execute($insert_stmt);
    mysqli_stmt_close($insert_stmt);
    
    // Refetch settings
    $stmt2 = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt2, "i", $partner_id);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);
    $settings = mysqli_fetch_assoc($result2);
    mysqli_stmt_close($stmt2);
}

// ========== GET NOTIFICATION SETTINGS ==========
$notificationSettings = [
    'email_notifications' => true,
    'sms_notifications' => false,
    'whatsapp_notifications' => false,
    'lead_assigned' => true,
    'lead_converted' => true,
    'payout_processed' => true,
    'weekly_report' => true,
    'marketing_emails' => false
];

$notifTable = 'partner_notification_settings';
$checkNotifTable = mysqli_query($conn, "SHOW TABLES LIKE '$notifTable'");
if (mysqli_num_rows($checkNotifTable) > 0) {
    $notif_query = "SELECT * FROM $notifTable WHERE partner_id = ?";
    $notif_stmt = mysqli_prepare($conn, $notif_query);
    mysqli_stmt_bind_param($notif_stmt, "i", $partner_id);
    mysqli_stmt_execute($notif_stmt);
    $notif_result = mysqli_stmt_get_result($notif_stmt);
    $db_notifications = mysqli_fetch_assoc($notif_result);
    mysqli_stmt_close($notif_stmt);
    
    if ($db_notifications) {
        $notificationSettings = [
            'email_notifications' => (bool)($db_notifications['email_notifications'] ?? true),
            'sms_notifications' => (bool)($db_notifications['sms_notifications'] ?? false),
            'whatsapp_notifications' => (bool)($db_notifications['whatsapp_notifications'] ?? false),
            'lead_assigned' => (bool)($db_notifications['lead_assigned_notify'] ?? true),
            'lead_converted' => (bool)($db_notifications['lead_converted_notify'] ?? true),
            'payout_processed' => (bool)($db_notifications['payout_notify'] ?? true),
            'weekly_report' => (bool)($db_notifications['weekly_report'] ?? true),
            'marketing_emails' => (bool)($db_notifications['marketing_emails'] ?? false)
        ];
    }
}

// ========== CALCULATE ADDITIONAL METRICS ==========
$total_leads = (int)($settings['total_leads'] ?? 0);
$total_converted = (int)($settings['total_converted'] ?? 0);
$conversion_rate = $total_leads > 0 ? round(($total_converted / $total_leads) * 100, 2) : 0;

// Mask bank account number for security
$account_masked = null;
if (!empty($settings['account_number'])) {
    $length = strlen($settings['account_number']);
    $visible = 4;
    $masked_length = max(0, $length - $visible);
    $account_masked = str_repeat('X', $masked_length) . substr($settings['account_number'], -$visible);
}

// ========== CHECK PROFILE COMPLETENESS ==========
$profile_fields = [
    'name' => !empty($settings['name']),
    'email' => !empty($settings['email']),
    'phone' => !empty($settings['phone']),
    'bank_name' => !empty($settings['bank_name']),
    'account_number' => !empty($settings['account_number']),
    'ifsc_code' => !empty($settings['ifsc_code'])
];
$completed_fields = count(array_filter($profile_fields));
$total_fields = count($profile_fields);
$profile_completeness = round(($completed_fields / $total_fields) * 100, 0);

// ========== GET MINIMUM PAYOUT THRESHOLD ==========
$min_payout_threshold = 500; // Minimum ₹500 for payout request

// ========== GENERATE REFERRAL LINK ==========
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$domain = $protocol . $host;
$referral_link = $domain . '/register.html?ref=' . ($settings['referral_code'] ?? 'partner_' . $partner_id);

// ========== FORMAT RESPONSE ==========
$response = [
    'success' => true,
    'partner' => [
        'id' => $settings['partner_id'],
        'name' => $settings['name'],
        'email' => $settings['email'],
        'phone' => $settings['phone'],
        'city' => $settings['city'],
        'account_status' => $settings['account_status'],
        'member_since' => $settings['member_since'],
        'company_name' => $settings['company_name'] ?? null
    ],
    'financial' => [
        'commission_rate' => (float)($settings['commission_rate'] ?? 10),
        'total_leads' => (int)($settings['total_leads'] ?? 0),
        'total_converted' => (int)($settings['total_converted'] ?? 0),
        'conversion_rate' => $conversion_rate,
        'total_commission' => (float)($settings['total_commission'] ?? 0),
        'pending_payout' => (float)($settings['pending_payout'] ?? 0),
        'min_payout_threshold' => $min_payout_threshold,
        'can_request_payout' => ((float)($settings['pending_payout'] ?? 0) >= $min_payout_threshold)
    ],
    'bank_details' => [
        'bank_name' => $settings['bank_name'] ?? null,
        'account_number_masked' => $account_masked,
        'ifsc_code' => $settings['ifsc_code'] ?? null,
        'account_holder' => $settings['account_holder'] ?? null,
        'has_bank_details' => !empty($settings['bank_name']) && !empty($settings['account_number'])
    ],
    'referral' => [
        'code' => $settings['referral_code'] ?? null,
        'link' => $referral_link
    ],
    'notifications' => $notificationSettings,
    'profile' => [
        'completeness' => $profile_completeness,
        'missing_fields' => array_keys(array_filter($profile_fields, function($v) { return !$v; }))
    ],
    'preferences' => [
        'timezone' => 'Asia/Kolkata',
        'date_format' => 'DD-MM-YYYY',
        'currency' => 'INR',
        'language' => 'en'
    ],
    'last_updated' => date('Y-m-d H:i:s')
];

// Add additional fields if they exist
if (isset($settings['referral_code']) && $settings['referral_code']) {
    $response['share_links'] = [
        'whatsapp' => "https://wa.me/?text=" . urlencode("Join CIBIL Repair using my referral link: " . $referral_link),
        'facebook' => "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($referral_link)
    ];
}

echo json_encode($response);

mysqli_close($conn);
?>