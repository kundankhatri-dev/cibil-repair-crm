<?php
// api/lead/add_lead.php - Add new lead
session_start();
header('Content-Type: application/json');

$allowed_roles = ['sales', 'bd', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '');
$email = trim($input['email'] ?? '');
$source = trim($input['source'] ?? 'website');
$notes = trim($input['notes'] ?? '');

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

$query = "INSERT INTO leads (name, phone, email, source, notes, stage, created_at) 
          VALUES (?, ?, ?, ?, ?, 'new', NOW())";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sssss", $name, $phone, $email, $source, $notes);
$inserted = mysqli_stmt_execute($stmt);
$lead_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

if ($inserted) {
    echo json_encode([
        'success' => true,
        'message' => 'Lead added successfully',
        'lead_id' => $lead_id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add lead']);
}

mysqli_close($conn);
?>