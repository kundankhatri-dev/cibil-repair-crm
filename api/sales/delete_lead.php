<?php
// api/sales/delete_lead.php
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

if (empty($lead_id)) {
    echo json_encode(['success' => false, 'error' => 'Lead ID is required']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Check if lead exists
$stmt = $conn->prepare("SELECT id FROM sales_leads WHERE id = ?");
$stmt->execute([$lead_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Lead not found']);
    exit;
}

// Delete related activities first (foreign key constraint)
$stmt = $conn->prepare("DELETE FROM sales_activities WHERE lead_id = ?");
$stmt->execute([$lead_id]);

// Delete lead
$stmt = $conn->prepare("DELETE FROM sales_leads WHERE id = ?");
$stmt->execute([$lead_id]);

echo json_encode([
    'success' => true,
    'message' => 'Lead deleted successfully'
]);
?>