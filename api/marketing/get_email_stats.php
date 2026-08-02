<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT * FROM email_campaigns ORDER BY sent_date DESC LIMIT 20";
$result = mysqli_query($conn, $query);

$campaigns = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Calculate open rate
    $open_rate = $row['recipients'] > 0 ? ($row['opens'] / $row['recipients']) * 100 : 0;
    $click_rate = $row['opens'] > 0 ? ($row['clicks'] / $row['opens']) * 100 : 0;
    $row['open_rate'] = round($open_rate, 2);
    $row['click_rate'] = round($click_rate, 2);
    $campaigns[] = $row;
}

// Overall averages
$avg_query = "SELECT AVG(open_rate) as avg_open_rate, AVG(click_rate) as avg_click_rate 
              FROM (
                SELECT (opens / recipients) * 100 as open_rate, 
                       (clicks / opens) * 100 as click_rate 
                FROM email_campaigns 
                WHERE recipients > 0 AND opens > 0
              ) as rates";
$avg_result = mysqli_query($conn, $avg_query);
$averages = mysqli_fetch_assoc($avg_result);

echo json_encode([
    'success' => true,
    'data' => $campaigns,
    'averages' => [
        'avg_open_rate' => round($averages['avg_open_rate'] ?? 0, 2),
        'avg_click_rate' => round($averages['avg_click_rate'] ?? 0, 2)
    ]
]);
?>