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

$fullname = isset($input['fullname']) ? trim($input['fullname']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (empty($fullname) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Name and email are required']);
    exit;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS disclaimer_consent (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$sql = "INSERT INTO disclaimer_consent (full_name, email, ip_address, user_agent) 
        VALUES ('$fullname', '$email', '$ip', '$user_agent')";

if (mysqli_query($conn, $sql)) {
    $id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Consent recorded',
        'id' => $id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>