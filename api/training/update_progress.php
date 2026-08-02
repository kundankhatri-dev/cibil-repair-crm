<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$enrollment_id = $data['enrollment_id'] ?? 0;
$progress = $data['progress'] ?? 0;
$score = $data['score'] ?? null;

if (!$enrollment_id) {
    echo json_encode(['success' => false, 'error' => 'Enrollment ID required']);
    exit;
}

$completion_date = $progress >= 100 ? ", completion_date = NOW()" : "";
$score_update = $score ? ", score = $score" : "";

$query = "UPDATE training_enrollments 
          SET progress_percentage = $progress $completion_date $score_update 
          WHERE id = $enrollment_id";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Progress updated']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>