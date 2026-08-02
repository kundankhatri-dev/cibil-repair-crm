<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['finance_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$employee_id = $input['employee_id'] ?? 0;
$type = $input['type'] ?? '';
$amount = $input['amount'] ?? 0;
$month = $input['month'] ?? date('Y-m');

if (!$employee_id || !$amount) {
    echo json_encode(['success' => false, 'error' => 'Employee and amount required']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$query = "INSERT INTO employee_incentives (employee_id, incentive_type, amount, month_year) VALUES ($employee_id, '$type', $amount, '$month')";
mysqli_query($conn, $query);
echo json_encode(['success' => true, 'message' => 'Incentive added']);
mysqli_close($conn);
?>