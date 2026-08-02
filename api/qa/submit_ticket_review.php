<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$ticket_id = $data['ticket_id'] ?? 0;
$resolution_accuracy = $data['resolution_accuracy'] ?? 0;
$communication_skills = $data['communication_skills'] ?? 0;
$empathy_score = $data['empathy_score'] ?? 0;
$compliance_score = $data['compliance_score'] ?? 0;
$response_time_score = $data['response_time_score'] ?? 7;
$comments = $data['comments'] ?? '';
$reviewer_id = $data['reviewer_id'] ?? 1;

if (!$ticket_id) {
    echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
    exit;
}

// Get agent_id from ticket
$query = "SELECT assigned_to FROM support_tickets WHERE id = $ticket_id";
$result = mysqli_query($conn, $query);
$ticket = mysqli_fetch_assoc($result);
$agent_id = $ticket['assigned_to'] ?? 1;

$quality_score = round(($resolution_accuracy + $communication_skills + $empathy_score + $compliance_score + $response_time_score) / 5, 1);

$query = "INSERT INTO qa_ticket_reviews (ticket_id, reviewer_id, agent_id, review_date, quality_score, 
          resolution_accuracy, communication_skills, empathy_score, compliance_score, response_time_score, comments) 
          VALUES ($ticket_id, $reviewer_id, $agent_id, CURDATE(), $quality_score, $resolution_accuracy, 
          $communication_skills, $empathy_score, $compliance_score, $response_time_score, '$comments')";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
?>