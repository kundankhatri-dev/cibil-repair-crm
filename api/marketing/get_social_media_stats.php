<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$period = $_GET['period'] ?? '30'; // days

$query = "SELECT platform,
          SUM(impressions) as total_impressions,
          SUM(likes) as total_likes,
          SUM(shares) as total_shares,
          SUM(comments) as total_comments,
          SUM(clicks) as total_clicks,
          COUNT(*) as total_posts
          FROM social_media_posts
          WHERE post_date >= DATE_SUB(NOW(), INTERVAL $period DAY)
          GROUP BY platform";
$result = mysqli_query($conn, $query);

$stats = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Engagement rate calculation
    $engagement = $row['total_likes'] + $row['total_shares'] + $row['total_comments'];
    $engagement_rate = $row['total_impressions'] > 0 ? ($engagement / $row['total_impressions']) * 100 : 0;
    $row['engagement_rate'] = round($engagement_rate, 2);
    $stats[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $stats,
    'period' => "$period days"
]);
?>