<?php
// api/sales/set_target.php
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
$month = isset($data['month']) ? (int)$data['month'] : 0;
$year = isset($data['year']) ? (int)$data['year'] : 0;
$target_amount = isset($data['target_amount']) ? (float)$data['target_amount'] : 0;

// Validate
if (empty($employee_id) || empty($month) || empty($year)) {
    echo json_encode(['success' => false, 'error' => 'Employee ID, month and year are required']);
    exit;
}

if ($target_amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Target amount must be greater than 0']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Check if target already exists for this month/year
$stmt = $conn->prepare("SELECT id FROM sales_targets WHERE sales_person_id = ? AND month = ? AND year = ?");
$stmt->execute([$employee_id, $month, $year]);
$existing = $stmt->fetch();

if ($existing) {
    // Update existing target
    $stmt = $conn->prepare("UPDATE sales_targets SET target_amount = ? WHERE sales_person_id = ? AND month = ? AND year = ?");
    $stmt->execute([$target_amount, $employee_id, $month, $year]);
    $message = "Target updated successfully";
} else {
    // Insert new target
    $stmt = $conn->prepare("INSERT INTO sales_targets (sales_person_id, month, year, target_amount) VALUES (?, ?, ?, ?)");
    $stmt->execute([$employee_id, $month, $year, $target_amount]);
    $message = "Target set successfully";
}

echo json_encode([
    'success' => true,
    'message' => $message
]);
?>