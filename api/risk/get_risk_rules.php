<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT * FROM risk_rules_config ORDER BY risk_score_weight DESC";
$result = mysqli_query($conn, $query);

$rules = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rules[] = $row;
}

echo json_encode(['success' => true, 'rules' => $rules, 'total' => count($rules)]);
?>