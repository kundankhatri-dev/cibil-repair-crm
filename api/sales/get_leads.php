<?php
// api/sales/get_leads.php
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
$search = isset($_GET['search']) ? $_GET['search'] : '';
$stage = isset($_GET['stage']) ? $_GET['stage'] : '';
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

$sql = "SELECT * FROM sales_leads WHERE 1=1 $emp_condition";
if (!empty($search)) {
    $sql .= " AND (client_name LIKE '%$search%' OR client_phone LIKE '%$search%' OR client_email LIKE '%$search%')";
}
if (!empty($stage)) {
    $sql .= " AND stage = '$stage'";
}
$sql .= " ORDER BY created_at DESC";

$leads = [];
$stmt = $conn->query($sql);
while ($row = $stmt->fetch()) {
    $leads[] = $row;
}

echo json_encode([
    'success' => true,
    'leads' => $leads
]);
?>