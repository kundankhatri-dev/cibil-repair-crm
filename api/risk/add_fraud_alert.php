<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$alert_type = $data['alert_type'] ?? '';
$entity_type = $data['entity_type'] ?? 'user';
$entity_id = $data['entity_id'] ?? 0;
$severity = $data['severity'] ?? 'medium';
$alert_details = $data['details'] ?? '';

if (!$alert_type || !$alert_details) {
    echo json_encode(['success' => false, 'error' => 'Alert type and details required']);
    exit;
}

$query = "INSERT INTO risk_fraud_alerts (alert_type, entity_type, entity_id, severity, alert_details, triggered_at) 
          VALUES ('$alert_type', '$entity_type', $entity_id, '$severity', '$alert_details', NOW())";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Alert created successfully', 'id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>