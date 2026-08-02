<?php
// api/sales/update_lead_stage.php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

// Check authentication
$allowed_roles = ['sales_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$lead_id = isset($data['lead_id']) ? (int)$data['lead_id'] : 0;
$stage = isset($data['stage']) ? $data['stage'] : '';
$notes = isset($data['notes']) ? $data['notes'] : '';

// Validate
if (empty($lead_id) || empty($stage)) {
    echo json_encode(['success' => false, 'error' => 'Lead ID and stage are required']);
    exit;
}

// Valid stages
$valid_stages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
if (!in_array($stage, $valid_stages)) {
    echo json_encode(['success' => false, 'error' => 'Invalid stage']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get previous stage for logging
$stmt = $conn->prepare("SELECT stage FROM sales_leads WHERE id = ?");
$stmt->execute([$lead_id]);
$previous_stage = $stmt->fetch()['stage'] ?? '';

// Update lead stage
$sql = "UPDATE sales_leads SET stage = ?, notes = CONCAT(notes, '\n---\n[', NOW(), '] Stage updated from ', stage, ' to ', ?) WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$stage, $stage, $lead_id]);

// Log activity automatically
$activity_sql = "INSERT INTO sales_activities (sales_person_id, lead_id, activity_type, subject, description, activity_date) 
                 SELECT sales_person_id, ?, 'update', 'Stage updated', ?, NOW() FROM sales_leads WHERE id = ?";
$activity_stmt = $conn->prepare($activity_sql);
$activity_stmt->execute([$lead_id, "Lead stage changed from {$previous_stage} to {$stage}\nNotes: {$notes}", $lead_id]);

echo json_encode([
    'success' => true,
    'message' => 'Lead stage updated successfully'
]);
?>