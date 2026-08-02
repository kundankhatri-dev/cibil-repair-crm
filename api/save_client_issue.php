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

$required = ['fullName', 'phone', 'email', 'city', 'issueType', 'problemDescription'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'error' => "Missing field: $field"]);
        exit;
    }
}

$fullName = trim($input['fullName']);
$phone = trim($input['phone']);
$email = trim($input['email']);
$city = trim($input['city']);
$issueType = trim($input['issueType']);
$problemDescription = trim($input['problemDescription']);
$additionalInfo = isset($input['additionalInfo']) ? trim($input['additionalInfo']) : '';
$attachments = isset($input['attachments']) ? json_encode($input['attachments']) : '';

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS client_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    problem_description TEXT NOT NULL,
    additional_info TEXT,
    attachments TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$sql = "INSERT INTO client_issues (full_name, phone, email, city, issue_type, problem_description, additional_info, attachments) 
        VALUES ('$fullName', '$phone', '$email', '$city', '$issueType', '$problemDescription', '$additionalInfo', '$attachments')";

if (mysqli_query($conn, $sql)) {
    $id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Issue submitted successfully',
        'issue_id' => $id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>