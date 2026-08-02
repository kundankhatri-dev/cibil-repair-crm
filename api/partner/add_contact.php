<?php
// ============================================================
// API: Add Contact
// ============================================================

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$category = $data['category'] ?? 'others';
$name = $data['name'] ?? '';
$role = $data['role'] ?? '';
$phone = $data['phone'] ?? '';
$email = $data['email'] ?? '';
$company = $data['company'] ?? '';
$city = $data['city'] ?? '';
$state = $data['state'] ?? '';
$pincode = $data['pincode'] ?? '';
$notes = $data['notes'] ?? '';

if (empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Name and phone are required']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$query = "INSERT INTO contacts (partner_id, category, name, role, phone, email, company, city, state, pincode, notes) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "issssssssss", $partner_id, $category, $name, $role, $phone, $email, $company, $city, $state, $pincode, $notes);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'id' => mysqli_insert_id($conn), 'message' => 'Contact added successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add contact: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>