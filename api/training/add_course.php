<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$course_name = $data['course_name'] ?? '';
$course_type = $data['course_type'] ?? 'technical';
$duration_hours = $data['duration_hours'] ?? 0;
$passing_score = $data['passing_score'] ?? 70;
$description = $data['description'] ?? '';
$is_mandatory = $data['is_mandatory'] ? 1 : 0;
$created_by = $data['created_by'] ?? 1;

if (!$course_name) {
    echo json_encode(['success' => false, 'error' => 'Course name required']);
    exit;
}

$query = "INSERT INTO training_courses (course_name, course_type, duration_hours, passing_score, description, is_mandatory, created_by) 
          VALUES ('$course_name', '$course_type', $duration_hours, $passing_score, '$description', $is_mandatory, $created_by)";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Course added successfully', 'id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>