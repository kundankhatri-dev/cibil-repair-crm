<?php
// ============================================================
// API: Update Contact - COMPLETE WORKING VERSION
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];

// Get input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit;
}

// Extract data
$contact_id = isset($data['contact_id']) ? (int)$data['contact_id'] : 0;
$category = isset($data['category']) ? trim($data['category']) : 'others';
$name = isset($data['name']) ? trim($data['name']) : '';
$role = isset($data['role']) ? trim($data['role']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$company = isset($data['company']) ? trim($data['company']) : '';
$city = isset($data['city']) ? trim($data['city']) : '';
$state = isset($data['state']) ? trim($data['state']) : '';
$pincode = isset($data['pincode']) ? trim($data['pincode']) : '';
$notes = isset($data['notes']) ? trim($data['notes']) : '';

// Validate
if (empty($contact_id) || empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Contact ID, name, and phone are required']);
    exit;
}

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// Check if contact exists
$check = mysqli_query($conn, "SELECT id FROM contacts WHERE id = $contact_id AND partner_id = $partner_id");
if (mysqli_num_rows($check) == 0) {
    echo json_encode(['success' => false, 'error' => 'Contact not found or access denied']);
    mysqli_close($conn);
    exit;
}

// Update using prepared statement
$query = "UPDATE contacts SET 
    category = ?, 
    name = ?, 
    role = ?, 
    phone = ?, 
    email = ?, 
    company = ?, 
    city = ?, 
    state = ?, 
    pincode = ?, 
    notes = ? 
WHERE id = ? AND partner_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ssssssssssii", 
    $category, $name, $role, $phone, $email, $company, $city, $state, $pincode, $notes, $contact_id, $partner_id
);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Contact updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>