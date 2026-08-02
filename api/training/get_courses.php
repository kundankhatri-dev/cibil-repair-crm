<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
if ($type) $where[] = "course_type = '$type'";
if ($search) $where[] = "(course_name LIKE '%$search%' OR course_code LIKE '%$search%')";

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT * FROM training_courses $where_clause ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$courses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $courses[] = $row;
}

echo json_encode(['success' => true, 'courses' => $courses, 'total' => count($courses)]);
?>