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
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$subject = isset($input['subject']) ? trim($input['subject']) : '';
$complaint = isset($input['complaint']) ? trim($input['complaint']) : '';

if (empty($fullname) || empty($email) || empty($phone) || empty($subject) || empty($complaint)) {
    echo json_encode(['success' => false, 'error' => 'All fields required']);
    exit;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS grievances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    subject VARCHAR(255),
    complaint_text TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$sql = "INSERT INTO grievances (full_name, email, phone, subject, complaint_text) 
        VALUES ('$fullname', '$email', '$phone', '$subject', '$complaint')";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Grievance submitted', 'id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>