<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT s.*, c.course_name 
          FROM training_sessions s
          JOIN training_courses c ON s.course_id = c.id
          ORDER BY s.start_date ASC";
$result = mysqli_query($conn, $query);

$sessions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sessions[] = $row;
}

echo json_encode(['success' => true, 'sessions' => $sessions, 'total' => count($sessions)]);
?>