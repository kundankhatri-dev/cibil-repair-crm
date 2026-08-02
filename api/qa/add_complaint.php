<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$client_id = $data['client_id'] ?? 0;
$category_id = $data['category_id'] ?? 0;
$subject = $data['subject'] ?? '';
$description = $data['description'] ?? '';
$severity = $data['severity'] ?? 'medium';
$assigned_to = $data['assigned_to'] ?? null;
$created_by = $data['created_by'] ?? 1;

if (!$client_id || !$category_id || !$subject) {
    echo json_encode(['success' => false, 'error' => 'Client, category, and subject required']);
    exit;
}

$assigned_val = $assigned_to ? $assigned_to : 'NULL';

$query = "INSERT INTO qa_complaints (client_id, category_id, subject, description, severity, assigned_to, created_by, status) 
          VALUES ($client_id, $category_id, '$subject', '$description', '$severity', $assigned_val, $created_by, 'open')";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Complaint logged successfully']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>