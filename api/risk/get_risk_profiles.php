<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT * FROM risk_profiles ORDER BY risk_score DESC";
$result = mysqli_query($conn, $query);

$profiles = [];
while ($row = mysqli_fetch_assoc($result)) {
    $profiles[] = $row;
}

echo json_encode(['success' => true, 'profiles' => $profiles, 'total' => count($profiles)]);
?>