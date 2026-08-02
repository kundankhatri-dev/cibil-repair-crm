<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

try {
    // Average quality score
    $query = "SELECT IFNULL(AVG(quality_score), 0) as avg_score FROM qa_ticket_reviews WHERE review_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $result = mysqli_query($conn, $query);
    $avg_quality_score = round(mysqli_fetch_assoc($result)['avg_score'], 1);
    
    // Open complaints
    $query = "SELECT COUNT(*) as total FROM qa_complaints WHERE status IN ('open', 'investigating')";
    $result = mysqli_query($conn, $query);
    $open_complaints = mysqli_fetch_assoc($result)['total'];
    
    // Average CSAT
    $query = "SELECT IFNULL(AVG(csat_score), 0) as avg_csat FROM qa_agent_performance WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $result = mysqli_query($conn, $query);
    $avg_csat = round(mysqli_fetch_assoc($result)['avg_csat'], 1);
    
    // Evaluations count
    $query = "SELECT COUNT(*) as total FROM qa_agent_evaluations WHERE evaluation_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $result = mysqli_query($conn, $query);
    $evaluations_count = mysqli_fetch_assoc($result)['total'];
    
    // Trend data for chart (last 6 months)
    $query = "SELECT DATE_FORMAT(review_date, '%Y-%m') as month, AVG(quality_score) as avg_score 
              FROM qa_ticket_reviews 
              WHERE review_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(review_date, '%Y-%m')
              ORDER BY month";
    $result = mysqli_query($conn, $query);
    $trend_data = ['labels' => [], 'values' => []];
    while ($row = mysqli_fetch_assoc($result)) {
        $trend_data['labels'][] = $row['month'];
        $trend_data['values'][] = round($row['avg_score'], 1);
    }
    
    // Complaint categories distribution
    $query = "SELECT c.category_name, COUNT(*) as count 
              FROM qa_complaints qc
              JOIN qa_complaint_categories c ON qc.category_id = c.id
              GROUP BY c.category_name";
    $result = mysqli_query($conn, $query);
    $complaint_categories = ['labels' => [], 'values' => []];
    while ($row = mysqli_fetch_assoc($result)) {
        $complaint_categories['labels'][] = $row['category_name'];
        $complaint_categories['values'][] = (int)$row['count'];
    }
    
    // Top performing agents
    $query = "SELECT u.name as agent_name, 
              AVG(ap.csat_score) as avg_csat, 
              AVG(ap.quality_score) as avg_quality, 
              SUM(ap.tickets_resolved) as total_resolved
              FROM qa_agent_performance ap
              JOIN users u ON ap.agent_id = u.id
              WHERE ap.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              GROUP BY ap.agent_id
              ORDER BY avg_quality DESC
              LIMIT 5";
    $result = mysqli_query($conn, $query);
    $top_agents = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $top_agents[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'avg_quality_score' => $avg_quality_score,
        'open_complaints' => (int)$open_complaints,
        'avg_csat' => $avg_csat,
        'evaluations_count' => (int)$evaluations_count,
        'trend_data' => $trend_data,
        'complaint_categories' => $complaint_categories,
        'top_agents' => $top_agents
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>