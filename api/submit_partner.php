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

$full_name = isset($input['full_name']) ? trim($input['full_name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$city = isset($input['city']) ? trim($input['city']) : '';
$state = isset($input['state']) ? trim($input['state']) : '';
$pincode = isset($input['pincode']) ? trim($input['pincode']) : '';
$company_name = isset($input['company_name']) ? trim($input['company_name']) : '';
$experience = isset($input['experience']) ? trim($input['experience']) : '';
$source = isset($input['source']) ? trim($input['source']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';

if (empty($full_name) || empty($email) || empty($phone) || empty($city) || empty($state)) {
    echo json_encode(['success' => false, 'error' => 'All fields required']);
    exit;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS partner_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    city VARCHAR(100),
    state VARCHAR(100),
    pincode VARCHAR(10),
    company_name VARCHAR(255),
    experience VARCHAR(50),
    source VARCHAR(100),
    message TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$sql = "INSERT INTO partner_applications (full_name, email, phone, city, state, pincode, company_name, experience, source, message) 
        VALUES ('$full_name', '$email', '$phone', '$city', '$state', '$pincode', '$company_name', '$experience', '$source', '$message')";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Application submitted', 'id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>