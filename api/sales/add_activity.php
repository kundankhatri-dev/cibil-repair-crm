    <?php
// api/sales/add_activity.php
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

$employee_id = isset($data['employee_id']) ? (int)$data['employee_id'] : 0;
$lead_id = isset($data['lead_id']) ? (int)$data['lead_id'] : 0;
$activity_type = isset($data['activity_type']) ? $data['activity_type'] : '';
$subject = isset($data['subject']) ? trim($data['subject']) : '';
$description = isset($data['description']) ? trim($data['description']) : '';
$activity_date = isset($data['activity_date']) ? $data['activity_date'] : date('Y-m-d H:i:s');
$outcome = isset($data['outcome']) ? trim($data['outcome']) : '';

// Validate
if (empty($activity_type)) {
    echo json_encode(['success' => false, 'error' => 'Activity type is required']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$sql = "INSERT INTO sales_activities (sales_person_id, lead_id, activity_type, subject, description, activity_date, outcome, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->execute([
    $employee_id, $lead_id, $activity_type, $subject, $description, 
    $activity_date, $outcome
]);

$activity_id = $conn->lastInsertId();

// Also update lead's last contact date if activity is call/meeting/email
if (in_array($activity_type, ['call', 'meeting', 'email']) && $lead_id > 0) {
    $update_sql = "UPDATE sales_leads SET updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([$lead_id]);
}

echo json_encode([
    'success' => true,
    'activity_id' => $activity_id,
    'message' => 'Activity logged successfully'
]);
?>