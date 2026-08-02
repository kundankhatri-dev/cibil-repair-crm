<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$session_name = $data['session_name'] ?? '';
$course_id = $data['course_id'] ?? 0;
$trainer_name = $data['trainer_name'] ?? '';
$session_type = $data['session_type'] ?? 'virtual';
$start_date = $data['start_date'] ?? '';
$end_date = $data['end_date'] ?? '';
$max_capacity = $data['max_capacity'] ?? 50;
$created_by = $data['created_by'] ?? 1;

if (!$session_name || !$course_id || !$start_date) {
    echo json_encode(['success' => false, 'error' => 'Session name, course, and start date required']);
    exit;
}

$query = "INSERT INTO training_sessions (session_name, course_id, trainer_name, session_type, start_date, end_date, max_capacity, created_by) 
          VALUES ('$session_name', $course_id, '$trainer_name', '$session_type', '$start_date', '$end_date', $max_capacity, $created_by)";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Session scheduled successfully']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>