<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT * FROM lead_sources WHERE is_active = 1 ORDER BY conversion_rate DESC";
$result = mysqli_query($conn, $query);

$sources = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sources[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $sources,
    'total' => count($sources)
]);
?>