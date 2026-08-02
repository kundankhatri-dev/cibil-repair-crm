<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$email = $_GET['email'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Valid email required']);
    exit;
}

$code = bin2hex(random_bytes(32));
$tempPass = bin2hex(random_bytes(4));
$hashedPass = password_hash($tempPass, PASSWORD_DEFAULT);

// Create table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS registration_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) UNIQUE NOT NULL,
    assigned_to_email VARCHAR(255) NOT NULL,
    temp_password VARCHAR(255) NOT NULL,
    created_for_role VARCHAR(50) DEFAULT 'partner',
    is_used TINYINT DEFAULT 0,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$sql = "INSERT INTO registration_codes (code, assigned_to_email, temp_password, created_for_role, expires_at) 
        VALUES ('$code', '$email', '$hashedPass', 'partner', DATE_ADD(NOW(), INTERVAL 7 DAY))";

if (mysqli_query($conn, $sql)) {
    $link = "https://cibilrepair.in/register.php?code=$code&email=" . urlencode($email);
    echo json_encode([
        'success' => true,
        'data' => [
            'code' => $code,
            'email' => $email,
            'temp_password' => $tempPass,
            'registration_link' => $link
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>