<?php
header('Content-Type: application/json');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$email = $_GET['email'] ?? 'partner@test.com';

$code = bin2hex(random_bytes(32));
$tempPass = 'temp' . rand(1000, 9999);

// Create table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS registration_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) UNIQUE,
    assigned_to_email VARCHAR(255),
    temp_password VARCHAR(255),
    created_for_role VARCHAR(50),
    is_used TINYINT DEFAULT 0,
    expires_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$sql = "INSERT INTO registration_codes (code, assigned_to_email, temp_password, created_for_role, expires_at) 
        VALUES ('$code', '$email', '$tempPass', 'partner', DATE_ADD(NOW(), INTERVAL 7 DAY))";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        'success' => true,
        'code' => $code,
        'email' => $email,
        'temp_password' => $tempPass,
        'link' => "https://cibilrepair.in/register.php?code=$code&email=" . urlencode($email)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>