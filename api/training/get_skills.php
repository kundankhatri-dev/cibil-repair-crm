<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT es.*, u.name as user_name, s.skill_name, 
          (SELECT name FROM users WHERE id = es.assessed_by) as assessed_by_name
          FROM training_employee_skills es
          JOIN users u ON es.user_id = u.id
          JOIN training_skills s ON es.skill_id = s.id
          ORDER BY es.created_at DESC";
$result = mysqli_query($conn, $query);

$skills = [];
while ($row = mysqli_fetch_assoc($result)) {
    $skills[] = $row;
}

echo json_encode(['success' => true, 'skills' => $skills, 'total' => count($skills)]);
?>