<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$campaign_name = $data['campaign_name'] ?? '';
$campaign_type = $data['campaign_type'] ?? 'email';
$start_date = $data['start_date'] ?? null;
$end_date = $data['end_date'] ?? null;
$budget = $data['budget'] ?? 0;
$expected_revenue = $data['expected_revenue'] ?? 0;
$status = $data['status'] ?? 'planned';
$created_by = $data['created_by'] ?? 1;

$query = "INSERT INTO marketing_campaigns (campaign_name, campaign_type, start_date, end_date, budget, expected_revenue, status, created_by) 
          VALUES ('$campaign_name', '$campaign_type', '$start_date', '$end_date', $budget, $expected_revenue, '$status', $created_by)";

if (mysqli_query($conn, $query)) {
    echo json_encode([
        'success' => true,
        'message' => 'Campaign added successfully',
        'id' => mysqli_insert_id($conn)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add campaign: ' . mysqli_error($conn)
    ]);
}
?>