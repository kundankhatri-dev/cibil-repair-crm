<?php
// api/client/update_profile.php - Update client profile details
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

// Get client_id (only client can update their own profile)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';

// Only client can update profile (admins/partners cannot modify client profiles)
if ($viewer_role !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Only clients can update their own profile']);
    exit;
}

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

// ========== VALIDATE INPUT ==========
$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '');
$alternate_phone = trim($input['alternate_phone'] ?? '');
$date_of_birth = trim($input['date_of_birth'] ?? '');
$gender = trim($input['gender'] ?? '');

// Address fields
$address_line1 = trim($input['address_line1'] ?? '');
$address_line2 = trim($input['address_line2'] ?? '');
$city = trim($input['city'] ?? '');
$state = trim($input['state'] ?? '');
$state_code = trim($input['state_code'] ?? '');
$pincode = trim($input['pincode'] ?? '');
$country = trim($input['country'] ?? 'India');

// KYC fields
$pan_number = strtoupper(trim($input['pan_number'] ?? ''));
$aadhar_last4 = trim($input['aadhar_last4'] ?? '');
$voter_id = strtoupper(trim($input['voter_id'] ?? ''));
$passport_number = strtoupper(trim($input['passport_number'] ?? ''));

// Employment fields
$employment_type = trim($input['employment_type'] ?? '');
$employer_name = trim($input['employer_name'] ?? '');
$occupation = trim($input['occupation'] ?? '');
$annual_income = isset($input['annual_income']) ? (float)$input['annual_income'] : null;

// Banking fields
$bank_name = trim($input['bank_name'] ?? '');
$account_number = trim($input['account_number'] ?? '');
$ifsc_code = strtoupper(trim($input['ifsc_code'] ?? ''));
$upi_id = trim($input['upi_id'] ?? '');

// Preferences
$preferred_language = trim($input['preferred_language'] ?? 'en');
$email_notifications = isset($input['email_notifications']) ? (int)$input['email_notifications'] : 1;
$sms_notifications = isset($input['sms_notifications']) ? (int)$input['sms_notifications'] : 1;
$whatsapp_notifications = isset($input['whatsapp_notifications']) ? (int)$input['whatsapp_notifications'] : 0;

// ========== VALIDATION RULES ==========
$errors = [];

// Name validation
if (empty($name)) {
    $errors[] = "Name is required";
} elseif (strlen($name) < 2) {
    $errors[] = "Name must be at least 2 characters";
} elseif (strlen($name) > 100) {
    $errors[] = "Name must be less than 100 characters";
}

// Phone validation
if (!empty($phone)) {
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Invalid phone number. Must be 10 digits";
    } else {
        // Check if phone is already used by another client
        $check_phone = mysqli_prepare($conn, "SELECT id FROM users WHERE phone = ? AND id != ? AND role = 'client'");
        mysqli_stmt_bind_param($check_phone, "si", $phone, $client_id);
        mysqli_stmt_execute($check_phone);
        $phone_result = mysqli_stmt_get_result($check_phone);
        if (mysqli_fetch_assoc($phone_result)) {
            $errors[] = "Phone number already registered by another user";
        }
        mysqli_stmt_close($check_phone);
    }
}

// Alternate phone validation
if (!empty($alternate_phone) && !preg_match('/^[0-9]{10}$/', $alternate_phone)) {
    $errors[] = "Invalid alternate phone number. Must be 10 digits";
}

// Date of birth validation
if (!empty($date_of_birth)) {
    $dob_timestamp = strtotime($date_of_birth);
    if (!$dob_timestamp) {
        $errors[] = "Invalid date of birth format";
    } else {
        $age = date('Y') - date('Y', $dob_timestamp);
        if ($age < 18) {
            $errors[] = "You must be at least 18 years old";
        }
        if ($age > 100) {
            $errors[] = "Invalid date of birth";
        }
    }
}

// Gender validation
if (!empty($gender) && !in_array($gender, ['male', 'female', 'other', 'prefer_not_to_say'])) {
    $errors[] = "Invalid gender selection";
}

// Pincode validation
if (!empty($pincode) && !preg_match('/^[0-9]{6}$/', $pincode)) {
    $errors[] = "Invalid pincode. Must be 6 digits";
}

// PAN validation (Indian PAN format)
if (!empty($pan_number)) {
    if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan_number)) {
        $errors[] = "Invalid PAN number format. Example: ABCDE1234F";
    } else {
        // Check if PAN is already used
        $check_pan = mysqli_prepare($conn, "SELECT client_id FROM client_profiles WHERE pan_number = ? AND client_id != ?");
        mysqli_stmt_bind_param($check_pan, "si", $pan_number, $client_id);
        mysqli_stmt_execute($check_pan);
        $pan_result = mysqli_stmt_get_result($check_pan);
        if (mysqli_fetch_assoc($pan_result)) {
            $errors[] = "PAN number already registered by another user";
        }
        mysqli_stmt_close($check_pan);
    }
}

// Aadhar last 4 validation
if (!empty($aadhar_last4) && !preg_match('/^[0-9]{4}$/', $aadhar_last4)) {
    $errors[] = "Aadhar last 4 digits must be 4 digits";
}

// IFSC validation
if (!empty($ifsc_code) && !preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc_code)) {
    $errors[] = "Invalid IFSC code format";
}

// Annual income validation
if ($annual_income !== null && $annual_income < 0) {
    $errors[] = "Annual income cannot be negative";
}

// Employment type validation
if (!empty($employment_type) && !in_array($employment_type, ['salaried', 'self_employed', 'business', 'retired', 'student', 'unemployed'])) {
    $errors[] = "Invalid employment type";
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ========== UPDATE MAIN USERS TABLE ==========
$update_user = "UPDATE users SET name = ?, phone = ? WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $update_user);
mysqli_stmt_bind_param($user_stmt, "ssi", $name, $phone, $client_id);
$user_updated = mysqli_stmt_execute($user_stmt);
mysqli_stmt_close($user_stmt);

if (!$user_updated) {
    echo json_encode(['success' => false, 'error' => 'Failed to update user information']);
    exit;
}

// ========== UPDATE OR INSERT CLIENT PROFILE ==========
// Check if profile exists
$check_profile = mysqli_prepare($conn, "SELECT id FROM client_profiles WHERE client_id = ?");
mysqli_stmt_bind_param($check_profile, "i", $client_id);
mysqli_stmt_execute($check_profile);
$profile_exists = mysqli_stmt_get_result($check_profile);
$has_profile = mysqli_fetch_assoc($profile_exists);
mysqli_stmt_close($check_profile);

// Parse name into first and last name
$name_parts = explode(' ', $name, 2);
$first_name = $name_parts[0];
$last_name = $name_parts[1] ?? '';

if ($has_profile) {
    // Update existing profile
    $update_profile = "UPDATE client_profiles SET 
                        first_name = ?,
                        last_name = ?,
                        alternate_phone = ?,
                        date_of_birth = ?,
                        gender = ?,
                        address_line1 = ?,
                        address_line2 = ?,
                        city = ?,
                        state = ?,
                        state_code = ?,
                        pincode = ?,
                        country = ?,
                        pan_number = ?,
                        aadhar_last4 = ?,
                        voter_id = ?,
                        passport_number = ?,
                        employment_type = ?,
                        employer_name = ?,
                        occupation = ?,
                        annual_income = ?,
                        bank_name = ?,
                        account_number = ?,
                        ifsc_code = ?,
                        upi_id = ?,
                        preferred_language = ?,
                        email_notifications = ?,
                        sms_notifications = ?,
                        whatsapp_notifications = ?,
                        profile_completed = 1,
                        updated_at = NOW()
                      WHERE client_id = ?";
    
    $profile_stmt = mysqli_prepare($conn, $update_profile);
    mysqli_stmt_bind_param($profile_stmt, 
        "sssssssssssssssssssdddssssiiii",
        $first_name, $last_name, $alternate_phone, $date_of_birth, $gender,
        $address_line1, $address_line2, $city, $state, $state_code, $pincode, $country,
        $pan_number, $aadhar_last4, $voter_id, $passport_number,
        $employment_type, $employer_name, $occupation, $annual_income,
        $bank_name, $account_number, $ifsc_code, $upi_id,
        $preferred_language, $email_notifications, $sms_notifications, $whatsapp_notifications,
        $client_id
    );
} else {
    // Insert new profile
    $insert_profile = "INSERT INTO client_profiles (
                        client_id, first_name, last_name, alternate_phone, date_of_birth, gender,
                        address_line1, address_line2, city, state, state_code, pincode, country,
                        pan_number, aadhar_last4, voter_id, passport_number,
                        employment_type, employer_name, occupation, annual_income,
                        bank_name, account_number, ifsc_code, upi_id,
                        preferred_language, email_notifications, sms_notifications, whatsapp_notifications,
                        profile_completed
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
    
    $profile_stmt = mysqli_prepare($conn, $insert_profile);
    mysqli_stmt_bind_param($profile_stmt, 
        "isssssssssssssssssssdddssssiiii",
        $client_id, $first_name, $last_name, $alternate_phone, $date_of_birth, $gender,
        $address_line1, $address_line2, $city, $state, $state_code, $pincode, $country,
        $pan_number, $aadhar_last4, $voter_id, $passport_number,
        $employment_type, $employer_name, $occupation, $annual_income,
        $bank_name, $account_number, $ifsc_code, $upi_id,
        $preferred_language, $email_notifications, $sms_notifications, $whatsapp_notifications
    );
}

$profile_updated = mysqli_stmt_execute($profile_stmt);
mysqli_stmt_close($profile_stmt);

if (!$profile_updated) {
    echo json_encode(['success' => false, 'error' => 'Failed to update profile information']);
    exit;
}

// ========== LOG THE ACTIVITY ==========
$activity_desc = "Profile updated: Name, contact, and personal information modified";
$log_activity = mysqli_prepare($conn, "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address, user_agent) VALUES (?, 'profile_update', ?, ?, ?)");
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
mysqli_stmt_bind_param($log_activity, "isss", $client_id, $activity_desc, $ip_address, $user_agent);
mysqli_stmt_execute($log_activity);
mysqli_stmt_close($log_activity);

// ========== UPDATE SESSION DATA ==========
if ($user_updated) {
    $_SESSION['user_name'] = $name;
    $_SESSION['client_name'] = $name;
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'message' => 'Profile updated successfully',
    'updated_fields' => [
        'name' => $name,
        'phone' => $phone,
        'profile_completed' => true
    ]
]);

mysqli_close($conn);
?>