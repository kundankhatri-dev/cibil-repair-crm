<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$response = [];

try {
    // Total courses
    $query = "SELECT COUNT(*) as total FROM training_courses WHERE is_active = 1";
    $result = mysqli_query($conn, $query);
    $total_courses = mysqli_fetch_assoc($result)['total'];
    
    // In progress training
    $query = "SELECT COUNT(*) as total FROM training_enrollments WHERE status = 'in_progress'";
    $result = mysqli_query($conn, $query);
    $in_progress = mysqli_fetch_assoc($result)['total'];
    
    // Completed training
    $query = "SELECT COUNT(*) as total FROM training_enrollments WHERE status = 'completed'";
    $result = mysqli_query($conn, $query);
    $completed = mysqli_fetch_assoc($result)['total'];
    
    // Expiring certifications (within 30 days)
    $query = "SELECT COUNT(*) as total FROM training_employee_certs WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    $result = mysqli_query($conn, $query);
    $expiring_soon = mysqli_fetch_assoc($result)['total'];
    
    // Trend data for chart (last 6 months)
    $query = "SELECT DATE_FORMAT(completion_date, '%Y-%m') as month, COUNT(*) as count 
              FROM training_enrollments 
              WHERE completion_date IS NOT NULL 
              AND completion_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(completion_date, '%Y-%m')
              ORDER BY month";
    $result = mysqli_query($conn, $query);
    $trend_data = ['labels' => [], 'values' => []];
    while ($row = mysqli_fetch_assoc($result)) {
        $trend_data['labels'][] = $row['month'];
        $trend_data['values'][] = (int)$row['count'];
    }
    
    // Course distribution by type
    $query = "SELECT course_type, COUNT(*) as count FROM training_courses GROUP BY course_type";
    $result = mysqli_query($conn, $query);
    $course_distribution = ['labels' => [], 'values' => []];
    while ($row = mysqli_fetch_assoc($result)) {
        $course_distribution['labels'][] = ucfirst(str_replace('_', ' ', $row['course_type']));
        $course_distribution['values'][] = (int)$row['count'];
    }
    
    // Recent enrollments
    $query = "SELECT e.id, e.progress_percentage, e.status, u.name as user_name, c.course_name 
              FROM training_enrollments e
              JOIN users u ON e.user_id = u.id
              JOIN training_courses c ON e.course_id = c.id
              ORDER BY e.created_at DESC LIMIT 5";
    $result = mysqli_query($conn, $query);
    $recent_enrollments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_enrollments[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'total_courses' => (int)$total_courses,
        'in_progress_training' => (int)$in_progress,
        'completed_training' => (int)$completed,
        'expiring_soon' => (int)$expiring_soon,
        'trend_data' => $trend_data,
        'course_distribution' => $course_distribution,
        'recent_enrollments' => $recent_enrollments
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>