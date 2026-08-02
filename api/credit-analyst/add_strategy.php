<?php
// api/credit-analyst/add_strategy.php - Add repair strategy
session_start();
header('Content-Type: application/json');

$allowed_roles = ['credit_analyst', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$issue_type = isset($input['issue_type']) ? trim($input['issue_type']) : '';
$strategy = isset($input['strategy']) ? trim($input['strategy']) : '';
$estimated_days = isset($input['estimated_days']) ? (int)$input['estimated_days'] : 0;
$success_rate = isset($input['success_rate']) ? (int)$input['success_rate'] : 0;

if (empty($strategy)) {
    echo json_encode(['success' => false, 'error' => 'Strategy is required']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$query = "INSERT INTO repair_strategies (issue_type, strategy, estimated_days, success_rate) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ssii", $issue_type, $strategy, $estimated_days, $success_rate);
$inserted = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($inserted) {
    echo json_encode(['success' => true, 'message' => 'Strategy added']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add strategy']);
}

mysqli_close($conn);
?>