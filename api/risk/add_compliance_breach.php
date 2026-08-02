<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$breach_type = $data['breach_type'] ?? '';
$severity = $data['severity'] ?? 'medium';
$description = $data['description'] ?? '';
$affected_entity = $data['affected_entity'] ?? '';
$reported_by = $data['reported_by'] ?? 1;

if (!$breach_type || !$description) {
    echo json_encode(['success' => false, 'error' => 'Breach type and description required']);
    exit;
}

$query = "INSERT INTO risk_compliance_breaches (breach_type, severity, description, affected_entity, reported_by, detected_at, status) 
          VALUES ('$breach_type', '$severity', '$description', '$affected_entity', $reported_by, NOW(), 'open')";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Breach reported successfully', 'id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>