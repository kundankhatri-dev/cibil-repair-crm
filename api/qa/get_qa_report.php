<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$report_type = $_GET['report_type'] ?? 'department';
$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-90 days'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');

if ($report_type == 'department') {
    $query = "SELECT u.department, ROUND(AVG(e.total_score), 1) as avg_score, COUNT(*) as eval_count
              FROM qa_agent_evaluations e
              JOIN users u ON e.agent_id = u.id
              WHERE e.evaluation_date BETWEEN '$from_date' AND '$to_date'
              GROUP BY u.department";
    $result = mysqli_query($conn, $query);
    $report_data = ['labels' => [], 'values' => []];
    while ($row = mysqli_fetch_assoc($result)) {
        $report_data['labels'][] = $row['department'] ?? 'General';
        $report_data['values'][] = $row['avg_score'];
    }
} else {
    // Monthly trend
    $query = "SELECT DATE_FORMAT(evaluation_date, '%b %Y') as month, ROUND(AVG(total_score), 1) as avg_score
              FROM qa_agent_evaluations
              WHERE evaluation_date BETWEEN '$from_date' AND '$to_date'
              GROUP BY YEAR(evaluation_date), MONTH(evaluation_date)
              ORDER BY evaluation_date";
    $result = mysqli_query($conn, $query);
    $report_data = ['labels' => [], 'values' => []];
    while ($row = mysqli_fetch_assoc($result)) {
        $report_data['labels'][] = $row['month'];
        $report_data['values'][] = $row['avg_score'];
    }
}

echo json_encode(['success' => true, 'report_data' => $report_data]);
?>