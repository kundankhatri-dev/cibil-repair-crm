<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$complaint_id = $data['complaint_id'] ?? 0;
$resolution_notes = $data['resolution_notes'] ?? '';

if (!$complaint_id) {
    echo json_encode(['success' => false, 'error' => 'Complaint ID required']);
    exit;
}

$query = "UPDATE qa_complaints SET status = 'resolved', resolved_at = NOW(), resolution_notes = '$resolution_notes' WHERE id = $complaint_id";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Complaint resolved']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>