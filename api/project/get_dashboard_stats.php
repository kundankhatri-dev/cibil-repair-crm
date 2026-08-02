<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$response = [];

try {
    // Active projects
    $query = "SELECT COUNT(*) as active FROM pm_projects WHERE status IN ('planning', 'in_progress')";
    $result = mysqli_query($conn, $query);
    $active_projects = mysqli_fetch_assoc($result)['active'];
    
    // Completed projects
    $query = "SELECT COUNT(*) as completed FROM pm_projects WHERE status = 'completed'";
    $result = mysqli_query($conn, $query);
    $completed_projects = mysqli_fetch_assoc($result)['completed'];
    
    // Pending tasks
    $query = "SELECT COUNT(*) as pending FROM pm_tasks WHERE status != 'completed'";
    $result = mysqli_query($conn, $query);
    $pending_tasks = mysqli_fetch_assoc($result)['pending'];
    
    // Overdue tasks
    $query = "SELECT COUNT(*) as overdue FROM pm_tasks WHERE due_date < CURDATE() AND status != 'completed'";
    $result = mysqli_query($conn, $query);
    $overdue_tasks = mysqli_fetch_assoc($result)['overdue'];
    
    // Status distribution for chart
    $query = "SELECT status, COUNT(*) as count FROM pm_projects GROUP BY status";
    $result = mysqli_query($conn, $query);
    $status_distribution = ['labels' => [], 'values' => []];
    while ($row = mysqli_fetch_assoc($result)) {
        $status_distribution['labels'][] = ucfirst(str_replace('_', ' ', $row['status']));
        $status_distribution['values'][] = (int)$row['count'];
    }
    
    // Recent projects
    $query = "SELECT p.*, c.name as client_name 
              FROM pm_projects p 
              LEFT JOIN clients c ON p.client_id = c.id 
              ORDER BY p.created_at DESC LIMIT 5";
    $result = mysqli_query($conn, $query);
    $recent_projects = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_projects[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'active_projects' => (int)$active_projects,
        'completed_projects' => (int)$completed_projects,
        'pending_tasks' => (int)$pending_tasks,
        'overdue_tasks' => (int)$overdue_tasks,
        'status_distribution' => $status_distribution,
        'recent_projects' => $recent_projects
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>