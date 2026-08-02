<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection (simplified, like your working submit_lead.php)
$host = 'localhost';
$user = 'u929623538_cibilrepair';
$password = 'Kundanlaxmi@1995';
$database = 'u929623538_cibil';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get input
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

$name = trim($data['fullName'] ?? '');
$email = trim($data['email'] ?? '');
$mobile = trim($data['mobile'] ?? '');
$pan = strtoupper(trim($data['pan'] ?? ''));
$city = trim($data['city'] ?? '');
$state = trim($data['state'] ?? '');
$address = trim($data['address'] ?? '');
$company = trim($data['company'] ?? '');
$partnerType = trim($data['partnerType'] ?? 'individual');

// Validate required fields
if (empty($name) || empty($email) || empty($mobile) || empty($pan)) {
    echo json_encode(['success' => false, 'error' => 'Name, Email, Mobile, and PAN are required']);
    exit;
}

// Validate mobile
if (!preg_match('/^[0-9]{10}$/', $mobile)) {
    echo json_encode(['success' => false, 'error' => 'Mobile number must be 10 digits']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

// Check if email exists
$check = mysqli_query($conn, "SELECT id FROM partners WHERE email = '$email'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode(['success' => false, 'error' => 'Email already registered']);
    exit;
}

// Check if mobile exists
$check = mysqli_query($conn, "SELECT id FROM partners WHERE mobile = '$mobile'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode(['success' => false, 'error' => 'Mobile number already registered']);
    exit;
}

// Generate referral code
$referralCode = 'CRP' . strtoupper(substr(md5($email . time()), 0, 8));

// Insert into database
$sql = "INSERT INTO partners (name, email, mobile, pan_number, referral_code, city, state, address, company_name, partner_type, created_at) 
        VALUES ('$name', '$email', '$mobile', '$pan', '$referralCode', '$city', '$state', '$address', '$company', '$partnerType', NOW())";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        'success' => true, 
        'message' => 'Registration successful!',
        'referral_code' => $referralCode
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>