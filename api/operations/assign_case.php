<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['operations_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$case_id = $input['case_id'] ?? 0;
$employee_id = $input['employee_id'] ?? 0;
$priority = $input['priority'] ?? 'medium';
$due_date = $input['due_date'] ?? date('Y-m-d', strtotime('+7 days'));

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$assigned_by = $_SESSION['user_id'];
$sla_due = date('Y-m-d H:i:s', strtotime($due_date . ' 18:00:00'));

mysqli_query($conn, "UPDATE operation_cases SET assigned_to = $employee_id, priority = '$priority', sla_due = '$sla_due', status = 'in_progress' WHERE id = $case_id");
mysqli_query($conn, "INSERT INTO case_assignments (case_id, assigned_to, assigned_by, notes) VALUES ($case_id, $employee_id, $assigned_by, 'Assigned via operations dashboard')");

$ip = $_SERVER['REMOTE_ADDR'];
mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES ($assigned_by, 'case_assigned', 'Assigned case ID: $case_id to employee ID: $employee_id', '$ip')");

echo json_encode(['success' => true, 'message' => 'Case assigned']);
mysqli_close($conn);
?>