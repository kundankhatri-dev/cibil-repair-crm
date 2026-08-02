<?php
// ============================================================
// ADD NOTIFICATION DEBUG
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

// Step 1: File loaded
$response = ['step' => 1, 'message' => 'File loaded'];

// Step 2: Database connection
$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    $response['step'] = 2;
    $response['error'] = 'DB connection failed: ' . mysqli_connect_error();
    echo json_encode($response);
    exit;
}

$response['step'] = 2;
$response['message'] = 'DB connected';

// Step 3: Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;

$response['step'] = 3;
$response['user_id'] = $user_id;

// Step 4: Get input
$input = json_decode(file_get_contents('php://input'), true);

$response['step'] = 4;
$response['input'] = $input;

// Step 5: Everything worked
$response['step'] = 5;
$response['message'] = 'All steps passed!';

echo json_encode($response);
mysqli_close($conn);
?>