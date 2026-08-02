<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Campaign ID required']);
    exit;
}

$updates = [];
$fields = ['campaign_name', 'campaign_type', 'start_date', 'end_date', 'budget', 'actual_cost', 
           'expected_revenue', 'actual_revenue', 'leads_generated', 'conversions', 'status'];

foreach ($fields as $field) {
    if (isset($data[$field])) {
        $value = $data[$field];
        $updates[] = "$field = '$value'";
    }
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit;
}

$query = "UPDATE marketing_campaigns SET " . implode(', ', $updates) . " WHERE id = $id";

if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Campaign updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update: ' . mysqli_error($conn)]);
}
?>