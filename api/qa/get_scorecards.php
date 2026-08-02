<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT * FROM qa_scorecards WHERE is_active = 1 ORDER BY scorecard_name";
$result = mysqli_query($conn, $query);
$scorecards = [];
while ($row = mysqli_fetch_assoc($result)) {
    $scorecards[] = $row;
}

echo json_encode(['success' => true, 'scorecards' => $scorecards, 'total' => count($scorecards)]);
?>