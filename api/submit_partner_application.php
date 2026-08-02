<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$partner_type = isset($input['partner_type']) ? trim($input['partner_type']) : 'Individual';
$city = isset($input['city']) ? trim($input['city']) : '';
$state = isset($input['state']) ? trim($input['state']) : '';
$pincode = isset($input['pincode']) ? trim($input['pincode']) : '';
$company = isset($input['company']) ? trim($input['company']) : '';
$experience = isset($input['experience']) ? trim($input['experience']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$pan = isset($input['pan']) ? strtoupper(trim($input['pan'])) : '';
$aadhaar = isset($input['aadhaar']) ? trim($input['aadhaar']) : '';
$ref_code = isset($input['ref_code']) ? trim($input['ref_code']) : '';

if (empty($name) || empty($email) || empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Name, Email and Phone are required']);
    exit;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS partner_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    partner_type VARCHAR(100),
    city VARCHAR(100),
    state VARCHAR(100),
    pincode VARCHAR(10),
    company VARCHAR(255),
    experience VARCHAR(50),
    message TEXT,
    pan VARCHAR(20),
    aadhaar VARCHAR(20),
    ref_code VARCHAR(50),
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$sql = "INSERT INTO partner_applications (name, email, phone, partner_type, city, state, pincode, company, experience, message, pan, aadhaar, ref_code) 
        VALUES ('$name', '$email', '$phone', '$partner_type', '$city', '$state', '$pincode', '$company', '$experience', '$message', '$pan', '$aadhaar', '$ref_code')";

if (mysqli_query($conn, $sql)) {
    $id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully',
        'id' => $id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>