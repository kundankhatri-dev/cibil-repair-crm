<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? 0;
$course_id = $data['course_id'] ?? 0;

if (!$user_id || !$course_id) {
    echo json_encode(['success' => false, 'error' => 'User ID and Course ID required']);
    exit;
}

$query = "INSERT INTO training_enrollments (user_id, course_id, enrollment_date, status, progress_percentage) 
          VALUES ($user_id, $course_id, CURDATE(), 'not_started', 0)
          ON DUPLICATE KEY UPDATE status = 'not_started', progress_percentage = 0";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Course assigned successfully']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>