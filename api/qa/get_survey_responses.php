<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT sr.*, s.survey_name, c.name as client_name 
          FROM qa_survey_responses sr
          JOIN qa_surveys s ON sr.survey_id = s.id
          JOIN clients c ON sr.client_id = c.id
          ORDER BY sr.submitted_at DESC
          LIMIT 100";
$result = mysqli_query($conn, $query);
$responses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $responses[] = $row;
}

echo json_encode(['success' => true, 'responses' => $responses, 'total' => count($responses)]);
?>