<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT e.*, u.name as agent_name, ev.name as evaluator_name, s.scorecard_name 
          FROM qa_agent_evaluations e
          JOIN users u ON e.agent_id = u.id
          JOIN users ev ON e.evaluator_id = ev.id
          JOIN qa_scorecards s ON e.scorecard_id = s.id
          ORDER BY e.evaluation_date DESC
          LIMIT 50";
$result = mysqli_query($conn, $query);
$evaluations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $evaluations[] = $row;
}

echo json_encode(['success' => true, 'evaluations' => $evaluations, 'total' => count($evaluations)]);
?>