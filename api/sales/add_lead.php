<?php
// api/sales/add_lead.php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

// Check authentication
$allowed_roles = ['sales_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$employee_id = isset($data['employee_id']) ? (int)$data['employee_id'] : 0;
$client_name = isset($data['client_name']) ? trim($data['client_name']) : '';
$client_phone = isset($data['client_phone']) ? trim($data['client_phone']) : '';
$client_email = isset($data['client_email']) ? trim($data['client_email']) : '';
$service_interest = isset($data['service_interest']) ? $data['service_interest'] : '';
$expected_amount = isset($data['expected_amount']) ? (float)$data['expected_amount'] : 0;
$probability = isset($data['probability']) ? (int)$data['probability'] : 20;
$source = isset($data['source']) ? $data['source'] : 'Website';
$expected_close_date = isset($data['expected_close_date']) ? $data['expected_close_date'] : null;
$notes = isset($data['notes']) ? $data['notes'] : '';

// Validate
if (empty($client_name)) {
    echo json_encode(['success' => false, 'error' => 'Client name is required']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$sql = "INSERT INTO sales_leads (sales_person_id, client_name, client_phone, client_email, service_interest, expected_amount, probability, source, expected_close_date, notes, stage, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())";

$stmt = $conn->prepare($sql);
$stmt->execute([
    $employee_id, $client_name, $client_phone, $client_email, 
    $service_interest, $expected_amount, $probability, $source, 
    $expected_close_date, $notes
]);

$lead_id = $conn->lastInsertId();

echo json_encode([
    'success' => true,
    'lead_id' => $lead_id,
    'message' => 'Lead added successfully'
]);
?>