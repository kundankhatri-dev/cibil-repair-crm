<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$agent_id = $data['agent_id'] ?? 0;
$scorecard_id = $data['scorecard_id'] ?? 0;
$evaluation_date = $data['evaluation_date'] ?? date('Y-m-d');
$criteria_scores = json_encode($data['criteria_scores'] ?? []);
$total_score = $data['total_score'] ?? 0;
$strengths = mysqli_real_escape_string($conn, $data['strengths'] ?? '');
$areas_for_improvement = mysqli_real_escape_string($conn, $data['areas_for_improvement'] ?? '');
$action_plan = mysqli_real_escape_string($conn, $data['action_plan'] ?? '');
$next_evaluation_date = $data['next_evaluation_date'] ?? null;
$evaluator_id = $data['evaluator_id'] ?? 1;

if (!$agent_id || !$scorecard_id) {
    echo json_encode(['success' => false, 'error' => 'Agent and Scorecard required']);
    exit;
}

$next_eval_val = $next_evaluation_date ? "'$next_evaluation_date'" : 'NULL';

$query = "INSERT INTO qa_agent_evaluations (agent_id, evaluator_id, scorecard_id, evaluation_date, total_score, 
          criteria_scores, strengths, areas_for_improvement, action_plan, next_evaluation_date, status) 
          VALUES ($agent_id, $evaluator_id, $scorecard_id, '$evaluation_date', $total_score, '$criteria_scores', 
          '$strengths', '$areas_for_improvement', '$action_plan', $next_eval_val, 'submitted')";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Evaluation submitted successfully', 'id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>