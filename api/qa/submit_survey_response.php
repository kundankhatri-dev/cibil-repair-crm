<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$survey_id = $data['survey_id'] ?? 0;
$client_id = $data['client_id'] ?? 0;
$responses = json_encode($data['responses'] ?? []);
$overall_rating = $data['overall_rating'] ?? 0;
$feedback_text = $data['feedback_text'] ?? '';

if (!$survey_id || !$client_id) {
    echo json_encode(['success' => false, 'error' => 'Survey ID and Client ID required']);
    exit;
}

$query = "INSERT INTO qa_survey_responses (survey_id, client_id, responses, overall_rating, feedback_text) 
          VALUES ($survey_id, $client_id, '$responses', $overall_rating, '$feedback_text')";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Survey response submitted']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>