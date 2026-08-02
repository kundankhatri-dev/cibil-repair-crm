<?php
// api/sales/get_sales_funnel.php
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
    $emp_condition = $employee_id ? "AND sales_person_id = $employee_id" : "";
} else {
    $emp_condition = "AND sales_person_id = (SELECT id FROM employees WHERE user_id = $user_id)";
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get counts for each stage
$stages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
$stage_counts = [];

foreach ($stages as $stage) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sales_leads WHERE stage = ? $emp_condition");
    $stmt->execute([$stage]);
    $result = $stmt->fetch();
    $stage_counts[$stage] = $result['count'] ?? 0;
}

echo json_encode([
    'success' => true,
    'stages' => $stage_counts
]);
?>