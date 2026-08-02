<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
if ($status) $where[] = "e.status = '$status'";
if ($search) $where[] = "u.name LIKE '%$search%'";

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT e.*, u.name as user_name, c.course_name 
          FROM training_enrollments e
          JOIN users u ON e.user_id = u.id
          JOIN training_courses c ON e.course_id = c.id
          $where_clause
          ORDER BY e.created_at DESC";
$result = mysqli_query($conn, $query);

$enrollments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $enrollments[] = $row;
}

echo json_encode(['success' => true, 'enrollments' => $enrollments, 'total' => count($enrollments)]);
?>