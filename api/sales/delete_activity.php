<?php
// api/sales/delete_activity.php
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
$activity_id = isset($data['activity_id']) ? (int)$data['activity_id'] : 0;

if (empty($activity_id)) {
    echo json_encode(['success' => false, 'error' => 'Activity ID is required']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Check if activity exists and belongs to the user
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

if ($user_role == 'admin' || $user_role == 'manager') {
    $check_sql = "SELECT id FROM sales_activities WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$activity_id]);
} else {
    $employee_id_sql = "SELECT id FROM employees WHERE user_id = ?";
    $emp_stmt = $conn->prepare($employee_id_sql);
    $emp_stmt->execute([$user_id]);
    $employee = $emp_stmt->fetch();
    $employee_id = $employee['id'] ?? 0;
    
    $check_sql = "SELECT id FROM sales_activities WHERE id = ? AND sales_person_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$activity_id, $employee_id]);
}

if (!$check_stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Activity not found or unauthorized']);
    exit;
}

// Delete activity
$stmt = $conn->prepare("DELETE FROM sales_activities WHERE id = ?");
$stmt->execute([$activity_id]);

echo json_encode([
    'success' => true,
    'message' => 'Activity deleted successfully'
]);
?>