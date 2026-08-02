<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? 0;
$certification_id = $data['certification_id'] ?? 0;
$issued_date = $data['issued_date'] ?? date('Y-m-d');
$expiry_date = $data['expiry_date'] ?? date('Y-m-d', strtotime('+1 year'));
$certificate_number = $data['certificate_number'] ?? 'CERT-' . strtoupper(uniqid());

if (!$user_id || !$certification_id) {
    echo json_encode(['success' => false, 'error' => 'User ID and Certification ID required']);
    exit;
}

$query = "INSERT INTO training_employee_certs (user_id, certification_id, certificate_number, issued_date, expiry_date) 
          VALUES ($user_id, $certification_id, '$certificate_number', '$issued_date', '$expiry_date')";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Certificate issued successfully']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>