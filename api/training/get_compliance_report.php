<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

// Compliance training completion by department
$query = "SELECT 
          CASE 
              WHEN u.role IN ('admin', 'manager') THEN 'Management'
              WHEN u.role = 'support_agent' THEN 'Support'
              ELSE 'Operations'
          END as department,
          COUNT(DISTINCT e.user_id) as total_employees,
          SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_count
          FROM training_enrollments e
          JOIN users u ON e.user_id = u.id
          JOIN training_courses c ON e.course_id = c.id
          WHERE c.course_type = 'compliance'
          GROUP BY department";
$result = mysqli_query($conn, $query);

$compliance_data = ['labels' => [], 'values' => []];
while ($row = mysqli_fetch_assoc($result)) {
    $compliance_data['labels'][] = $row['department'];
    $rate = $row['total_employees'] > 0 ? round(($row['completed_count'] / $row['total_employees']) * 100, 2) : 0;
    $compliance_data['values'][] = $rate;
}

echo json_encode(['success' => true, 'compliance_data' => $compliance_data]);
?>