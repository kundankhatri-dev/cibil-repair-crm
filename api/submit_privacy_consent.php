<?php
include '../config/database.php';  // ← This goes UP one level, then INTO config folder

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// ========== DATABASE CONFIGURATION (CHANGE THESE) ==========
$DB_HOST = 'localhost';
$DB_USER = 'u929623538_cibilrepair';
$DB_PASS = 'Kundanlaxmi@1995';
$DB_NAME = 'u929623538_cibil';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

$input = json_decode(file_get_contents('php://input'), true);
$fullname = isset($input['fullname']) ? $conn->real_escape_string($input['fullname']) : '';
$email = isset($input['email']) ? $conn->real_escape_string($input['email']) : '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$stmt = $conn->prepare("INSERT INTO privacy_consent (full_name, email, ip_address, user_agent) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $fullname, $email, $ip, $user_agent);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$stmt->close();
$conn->close();
?>