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
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$aadhar = isset($input['aadhar']) ? trim($input['aadhar']) : '';
$pan = isset($input['pan']) ? strtoupper(trim($input['pan'])) : '';
$issue = isset($input['issue']) ? trim($input['issue']) : '';
$score = isset($input['score']) ? trim($input['score']) : '';
$city = isset($input['city']) ? trim($input['city']) : '';

$isPartner = (strpos($message, 'Partner') !== false || strpos($issue, 'Partner') !== false);

if (empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Name and phone required']);
    exit;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    message TEXT,
    aadhar VARCHAR(20),
    pan VARCHAR(20),
    issue VARCHAR(255),
    score VARCHAR(50),
    city VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$sql = "INSERT INTO leads (name, phone, email, message, aadhar, pan, issue, score, city) 
        VALUES ('$name', '$phone', '$email', '$message', '$aadhar', '$pan', '$issue', '$score', '$city')";

if (mysqli_query($conn, $sql)) {
    $id = mysqli_insert_id($conn);
    
    if ($isPartner) {
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS partner_applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            email VARCHAR(255),
            phone VARCHAR(20),
            pan VARCHAR(20),
            aadhar VARCHAR(20),
            city VARCHAR(100),
            status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        mysqli_query($conn, "INSERT INTO partner_applications (name, email, phone, pan, aadhar, city) 
                            VALUES ('$name', '$email', '$phone', '$pan', '$aadhar', '$city')");
    }
    
    echo json_encode(['success' => true, 'message' => 'Submitted', 'id' => $id]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>