<?php
// api/sales/get_activities.php
require_once '../../config/database.php';
session_start();
header('Content-Type: application/json');

// Check authentication
$allowed_roles = ['sales_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// If admin/manager, can view all; otherwise only own
if ($user_role == 'admin' || $user_role == 'manager') {
    $emp_condition = $employee_id ? "AND sa.sales_person_id = $employee_id" : "";
} else {
    $emp_condition = "AND sa.sales_person_id = (SELECT id FROM employees WHERE user_id = $user_id)";
}

$db = Database::getInstance();
$conn = $db->getConnection();

$sql = "SELECT sa.*, sl.client_name 
        FROM sales_activities sa
        LEFT JOIN sales_leads sl ON sa.lead_id = sl.id
        WHERE 1=1 $emp_condition
        ORDER BY sa.activity_date DESC, sa.created_at DESC
        LIMIT 100";

$activities = [];
$stmt = $conn->query($sql);
while ($row = $stmt->fetch()) {
    $activities[] = $row;
}

echo json_encode([
    'success' => true,
    'activities' => $activities
]);
?>