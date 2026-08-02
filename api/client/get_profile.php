<?php
// api/client/get_profile.php - Get client profile details
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get client_id (supports both client and partner/admin viewing)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';
$viewer_id = $_SESSION['user_id'] ?? null;

// If partner or admin viewing, allow client_id from GET
if (in_array($viewer_role, ['admin', 'partner']) && isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
    
    // Verify partner has access to this client
    if ($viewer_role === 'partner' && $viewer_id) {
        $check = mysqli_prepare($conn, "SELECT COUNT(*) FROM leads WHERE partner_id = ? AND customer_id = ?");
        mysqli_stmt_bind_param($check, "ii", $viewer_id, $client_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_bind_result($check, $count);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);
        
        if ($count == 0) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
    }
}

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// ========== CREATE PROFILE TABLES IF NOT EXISTS ==========

// Extended client profile table
$create_profile = "CREATE TABLE IF NOT EXISTS client_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL UNIQUE,
    
    -- Personal Information
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    alternate_phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    
    -- Address Details
    address_line1 TEXT,
    address_line2 TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    state_code VARCHAR(10),
    pincode VARCHAR(10),
    country VARCHAR(100) DEFAULT 'India',
    
    -- KYC Details
    pan_number VARCHAR(20),
    aadhar_number VARCHAR(20),
    aadhar_last4 VARCHAR(4),
    voter_id VARCHAR(20),
    passport_number VARCHAR(20),
    
    -- Employment Details
    employment_type ENUM('salaried', 'self_employed', 'business', 'retired', 'student', 'unemployed'),
    employer_name VARCHAR(200),
    occupation VARCHAR(100),
    annual_income DECIMAL(12,2),
    income_proof_submitted TINYINT DEFAULT 0,
    
    -- Banking Details
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    ifsc_code VARCHAR(20),
    upi_id VARCHAR(100),
    
    -- Communication Preferences
    preferred_language VARCHAR(10) DEFAULT 'en',
    email_notifications TINYINT DEFAULT 1,
    sms_notifications TINYINT DEFAULT 1,
    whatsapp_notifications TINYINT DEFAULT 0,
    
    -- Metadata
    profile_completed TINYINT DEFAULT 0,
    kyc_verified TINYINT DEFAULT 0,
    kyc_verified_at DATETIME,
    kyc_verified_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_client (client_id),
    INDEX idx_pan (pan_number),
    INDEX idx_phone (phone),
    INDEX idx_kyc_verified (kyc_verified)
)";

mysqli_query($conn, $create_profile);

// Account activity log table
$create_activity = "CREATE TABLE IF NOT EXISTS client_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    activity_type VARCHAR(50),
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_type (activity_type),
    INDEX idx_created (created_at)
)";

mysqli_query($conn, $create_activity);

// Login history table
$create_login_history = "CREATE TABLE IF NOT EXISTS client_login_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_type VARCHAR(50),
    location VARCHAR(200),
    success TINYINT DEFAULT 1,
    logout_time DATETIME,
    INDEX idx_client (client_id),
    INDEX idx_login_time (login_time)
)";

mysqli_query($conn, $create_login_history);

// ========== GET CLIENT PROFILE ==========

// Get main user data
$user_query = "SELECT 
                u.id,
                u.name,
                u.email,
                u.phone,
                u.role,
                u.status,
                u.created_at as registered_date,
                u.last_login,
                u.last_login_ip,
                DATE_FORMAT(u.created_at, '%d %b %Y') as registered_date_formatted,
                DATE_FORMAT(u.last_login, '%d %b %Y %h:%i %p') as last_login_formatted
              FROM users u
              WHERE u.id = ? AND u.role = 'client'";

$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $client_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Client not found']);
    exit;
}

// Get extended profile
$profile_query = "SELECT * FROM client_profiles WHERE client_id = ?";
$profile_stmt = mysqli_prepare($conn, $profile_query);
mysqli_stmt_bind_param($profile_stmt, "i", $client_id);
mysqli_stmt_execute($profile_stmt);
$profile_result = mysqli_stmt_get_result($profile_stmt);
$profile = mysqli_fetch_assoc($profile_result);
mysqli_stmt_close($profile_stmt);

// If no extended profile exists, create one
if (!$profile) {
    $insert_profile = mysqli_prepare($conn, "INSERT INTO client_profiles (client_id) VALUES (?)");
    mysqli_stmt_bind_param($insert_profile, "i", $client_id);
    mysqli_stmt_execute($insert_profile);
    mysqli_stmt_close($insert_profile);
    
    // Fetch the newly created profile
    $profile_stmt = mysqli_prepare($conn, $profile_query);
    mysqli_stmt_bind_param($profile_stmt, "i", $client_id);
    mysqli_stmt_execute($profile_stmt);
    $profile_result = mysqli_stmt_get_result($profile_stmt);
    $profile = mysqli_fetch_assoc($profile_result);
    mysqli_stmt_close($profile_stmt);
}

// ========== GET ADDITIONAL INFO ==========

// Get recent login history (last 5)
$login_history_query = "SELECT 
                            login_time,
                            ip_address,
                            device_type,
                            location,
                            success,
                            DATE_FORMAT(login_time, '%d %b %Y %h:%i %p') as login_time_formatted
                        FROM client_login_history 
                        WHERE client_id = ? 
                        ORDER BY login_time DESC 
                        LIMIT 5";

$login_stmt = mysqli_prepare($conn, $login_history_query);
mysqli_stmt_bind_param($login_stmt, "i", $client_id);
mysqli_stmt_execute($login_stmt);
$login_result = mysqli_stmt_get_result($login_stmt);
$login_history = mysqli_fetch_all($login_result, MYSQLI_ASSOC);
mysqli_stmt_close($login_stmt);

// Get recent activity (last 10)
$activity_query = "SELECT 
                        activity_type,
                        description,
                        created_at,
                        DATE_FORMAT(created_at, '%d %b %Y %h:%i %p') as created_formatted
                    FROM client_activity_log 
                    WHERE client_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 10";

$activity_stmt = mysqli_prepare($conn, $activity_query);
mysqli_stmt_bind_param($activity_stmt, "i", $client_id);
mysqli_stmt_execute($activity_stmt);
$activity_result = mysqli_stmt_get_result($activity_stmt);
$recent_activity = mysqli_fetch_all($activity_result, MYSQLI_ASSOC);
mysqli_stmt_close($activity_stmt);

// Get document verification status
$doc_status_query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    FROM client_documents 
                    WHERE client_id = ? AND document_type IN ('Aadhar', 'PAN')";

$doc_stmt = mysqli_prepare($conn, $doc_status_query);
mysqli_stmt_bind_param($doc_stmt, "i", $client_id);
mysqli_stmt_execute($doc_stmt);
$doc_result = mysqli_stmt_get_result($doc_stmt);
$doc_status = mysqli_fetch_assoc($doc_result);
mysqli_stmt_close($doc_stmt);

// Get case statistics
$case_stats_query = "SELECT 
                        COUNT(*) as total_cases,
                        SUM(CASE WHEN status IN ('resolved', 'closed', 'converted') THEN 1 ELSE 0 END) as completed_cases,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_cases
                    FROM " . (mysqli_query($conn, "SHOW TABLES LIKE 'client_cases'") && mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'client_cases'")) > 0 ? "client_cases" : "leads") . " 
                    WHERE client_id = ?";

$case_stmt = mysqli_prepare($conn, $case_stats_query);
mysqli_stmt_bind_param($case_stmt, "i", $client_id);
mysqli_stmt_execute($case_stmt);
$case_result = mysqli_stmt_get_result($case_stmt);
$case_stats = mysqli_fetch_assoc($case_result);
mysqli_stmt_close($case_stmt);

// Get payment statistics
$payment_stats_query = "SELECT 
                            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_paid,
                            COUNT(CASE WHEN status = 'completed' THEN 1 END) as total_payments
                        FROM payments 
                        WHERE client_id = ? AND status = 'completed'";

$pay_stmt = mysqli_prepare($conn, $payment_stats_query);
mysqli_stmt_bind_param($pay_stmt, "i", $client_id);
mysqli_stmt_execute($pay_stmt);
$pay_result = mysqli_stmt_get_result($pay_stmt);
$payment_stats = mysqli_fetch_assoc($pay_result);
mysqli_stmt_close($pay_stmt);

// ========== MERGE PROFILE DATA ==========
$full_profile = array_merge($user, [
    'first_name' => $profile['first_name'] ?? '',
    'last_name' => $profile['last_name'] ?? '',
    'full_name' => trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')) ?: $user['name'],
    'alternate_phone' => $profile['alternate_phone'] ?? '',
    'date_of_birth' => $profile['date_of_birth'] ?? '',
    'date_of_birth_formatted' => $profile['date_of_birth'] ? date('d M Y', strtotime($profile['date_of_birth'])) : '',
    'gender' => $profile['gender'] ?? '',
    'address_line1' => $profile['address_line1'] ?? '',
    'address_line2' => $profile['address_line2'] ?? '',
    'city' => $profile['city'] ?? '',
    'state' => $profile['state'] ?? '',
    'state_code' => $profile['state_code'] ?? '',
    'pincode' => $profile['pincode'] ?? '',
    'country' => $profile['country'] ?? 'India',
    'full_address' => implode(', ', array_filter([
        $profile['address_line1'] ?? '',
        $profile['address_line2'] ?? '',
        $profile['city'] ?? '',
        $profile['state'] ?? '',
        $profile['pincode'] ?? ''
    ])),
    'pan_number' => $profile['pan_number'] ?? '',
    'pan_masked' => $profile['pan_number'] ? substr($profile['pan_number'], 0, 5) . 'XXXX' . substr($profile['pan_number'], -1) : '',
    'aadhar_last4' => $profile['aadhar_last4'] ?? '',
    'voter_id' => $profile['voter_id'] ?? '',
    'passport_number' => $profile['passport_number'] ?? '',
    'employment_type' => $profile['employment_type'] ?? '',
    'employer_name' => $profile['employer_name'] ?? '',
    'occupation' => $profile['occupation'] ?? '',
    'annual_income' => (float)($profile['annual_income'] ?? 0),
    'annual_income_formatted' => $profile['annual_income'] ? '₹' . number_format($profile['annual_income'], 2) : '',
    'income_proof_submitted' => (bool)($profile['income_proof_submitted'] ?? false),
    'bank_name' => $profile['bank_name'] ?? '',
    'account_number_masked' => $profile['account_number'] ? 'XXXX' . substr($profile['account_number'], -4) : '',
    'ifsc_code' => $profile['ifsc_code'] ?? '',
    'upi_id' => $profile['upi_id'] ?? '',
    'preferred_language' => $profile['preferred_language'] ?? 'en',
    'email_notifications' => (bool)($profile['email_notifications'] ?? true),
    'sms_notifications' => (bool)($profile['sms_notifications'] ?? true),
    'whatsapp_notifications' => (bool)($profile['whatsapp_notifications'] ?? false),
    'profile_completed' => (bool)($profile['profile_completed'] ?? false),
    'kyc_verified' => (bool)($profile['kyc_verified'] ?? false),
    'kyc_verified_at' => $profile['kyc_verified_at'] ?? '',
    'profile_completion_percentage' => calculateProfileCompletion($profile, $user),
    'member_since_days' => ceil((time() - strtotime($user['registered_date'])) / 86400)
]);

// ========== HELPER FUNCTIONS ==========
function calculateProfileCompletion($profile, $user) {
    $fields = [
        'first_name' => !empty($profile['first_name']),
        'last_name' => !empty($profile['last_name']),
        'phone' => !empty($user['phone']),
        'date_of_birth' => !empty($profile['date_of_birth']),
        'address_line1' => !empty($profile['address_line1']),
        'city' => !empty($profile['city']),
        'state' => !empty($profile['state']),
        'pincode' => !empty($profile['pincode']),
        'pan_number' => !empty($profile['pan_number']),
        'employment_type' => !empty($profile['employment_type'])
    ];
    
    $completed = count(array_filter($fields));
    $total = count($fields);
    
    return $total > 0 ? round(($completed / $total) * 100) : 0;
}

// ========== GET KYC STATUS ==========
$kyc_status = [
    'pan_verified' => ($doc_status['verified'] ?? 0) >= 1,
    'aadhar_uploaded' => ($doc_status['total'] ?? 0) >= 2,
    'overall' => ($profile['kyc_verified'] ?? false)
];

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'profile' => $full_profile,
    'kyc_status' => $kyc_status,
    'document_status' => [
        'total' => (int)($doc_status['total'] ?? 0),
        'verified' => (int)($doc_status['verified'] ?? 0),
        'pending' => (int)($doc_status['pending'] ?? 0),
        'rejected' => (int)($doc_status['rejected'] ?? 0)
    ],
    'case_stats' => [
        'total' => (int)($case_stats['total_cases'] ?? 0),
        'completed' => (int)($case_stats['completed_cases'] ?? 0),
        'pending' => (int)($case_stats['pending_cases'] ?? 0),
        'completion_rate' => ($case_stats['total_cases'] > 0) 
            ? round(($case_stats['completed_cases'] / $case_stats['total_cases']) * 100) 
            : 0
    ],
    'payment_stats' => [
        'total_paid' => (float)($payment_stats['total_paid'] ?? 0),
        'total_paid_formatted' => '₹' . number_format($payment_stats['total_paid'] ?? 0, 2),
        'total_payments' => (int)($payment_stats['total_payments'] ?? 0)
    ],
    'recent_activity' => $recent_activity,
    'login_history' => $login_history,
    'viewer_role' => $viewer_role
]);

mysqli_close($conn);
?>