<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$alert_id = $data['alert_id'] ?? 0;
$status = $data['status'] ?? '';
$resolution_notes = $data['resolution_notes'] ?? '';

if (!$alert_id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Alert ID and status required']);
    exit;
}

$query = "UPDATE risk_fraud_alerts SET resolution_status = '$status', resolution_notes = '$resolution_notes', reviewed_at = NOW() WHERE id = $alert_id";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Alert status updated']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>