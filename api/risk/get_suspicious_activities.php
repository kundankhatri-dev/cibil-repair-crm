<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT * FROM risk_suspicious_activities ORDER BY flagged_at DESC LIMIT 100";
$result = mysqli_query($conn, $query);

$activities = [];
while ($row = mysqli_fetch_assoc($result)) {
    $activities[] = $row;
}

echo json_encode(['success' => true, 'activities' => $activities, 'total' => count($activities)]);
?>