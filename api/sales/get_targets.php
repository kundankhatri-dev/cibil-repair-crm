<?php
// api/sales/get_targets.php
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

// Current month target
$currentMonth = date('m');
$currentYear = date('Y');

$stmt = $conn->prepare("SELECT target_amount FROM sales_targets WHERE month = ? AND year = ? $emp_condition");
$stmt->execute([$currentMonth, $currentYear]);
$target = $stmt->fetch();
$currentTarget = $target ? $target['target_amount'] : 0;

// Achieved this month (won deals)
$stmt = $conn->prepare("SELECT SUM(expected_amount) as achieved FROM sales_leads WHERE stage = 'won' AND MONTH(created_at) = ? AND YEAR(created_at) = ? $emp_condition");
$stmt->execute([$currentMonth, $currentYear]);
$achieved = $stmt->fetch();
$achievedAmount = $achieved['achieved'] ?? 0;

// Target history (last 12 months)
$history = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

for ($i = 11; $i >= 0; $i--) {
    $month = date('m', strtotime("-$i months"));
    $monthName = $months[(int)$month - 1];
    $year = date('Y', strtotime("-$i months"));
    
    // Get target for this month
    $stmt = $conn->prepare("SELECT target_amount FROM sales_targets WHERE month = ? AND year = ? $emp_condition");
    $stmt->execute([$month, $year]);
    $targetData = $stmt->fetch();
    $targetAmount = $targetData ? $targetData['target_amount'] : 0;
    
    // Get achieved for this month
    $stmt = $conn->prepare("SELECT SUM(expected_amount) as achieved FROM sales_leads WHERE stage = 'won' AND MONTH(created_at) = ? AND YEAR(created_at) = ? $emp_condition");
    $stmt->execute([$month, $year]);
    $achievedData = $stmt->fetch();
    $achievedAmountHist = $achievedData['achieved'] ?? 0;
    
    $percentage = $targetAmount > 0 ? round(($achievedAmountHist / $targetAmount) * 100) : 0;
    
    $history[] = [
        'month' => $month,
        'month_name' => $monthName,
        'year' => $year,
        'amount' => $targetAmount,
        'achieved' => $achievedAmountHist,
        'percentage' => $percentage
    ];
}

echo json_encode([
    'success' => true,
    'current_target' => [
        'amount' => $currentTarget
    ],
    'achieved' => $achievedAmount,
    'history' => $history
]);
?>