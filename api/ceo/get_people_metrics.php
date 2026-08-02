<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['ceo', 'founder', 'admin', 'director'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

// Team health metrics
$health_labels = ['Engagement', 'Productivity', 'Retention', 'Satisfaction', 'Growth', 'Learning'];
$health_values = [82, 78, 91, 85, 76, 88];

// Employee spotlights
$spotlights = [
    ['name' => 'Vikram Malhotra', 'achievement' => 'Completed 50+ cases this quarter', 'recognition' => 'Employee of the Quarter'],
    ['name' => 'Priya Sharma', 'achievement' => '98% client satisfaction rating', 'recognition' => 'Best Customer Service'],
    ['name' => 'Rahul Gupta', 'achievement' => 'Closed 15 new partner deals', 'recognition' => 'Top Performer']
];

echo json_encode(['success' => true, 'health_data' => ['labels' => $health_labels, 'values' => $health_values], 'spotlights' => $spotlights]);
mysqli_close($conn);
?>