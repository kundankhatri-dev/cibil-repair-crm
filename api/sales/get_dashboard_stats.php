<?php
// api/sales/get_dashboard_stats.php
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

// Total leads
$stmt = $conn->query("SELECT COUNT(*) as total FROM sales_leads WHERE 1=1 $emp_condition");
$totalLeads = $stmt->fetch()['total'];

// Won leads
$stmt = $conn->query("SELECT COUNT(*) as won FROM sales_leads WHERE stage = 'won' $emp_condition");
$wonLeads = $stmt->fetch()['won'];

// Total revenue from won leads
$stmt = $conn->query("SELECT SUM(expected_amount) as revenue FROM sales_leads WHERE stage = 'won' $emp_condition");
$revenue = $stmt->fetch()['revenue'] ?? 0;

// Pipeline value (active leads)
$stmt = $conn->query("SELECT SUM(expected_amount) as pipeline FROM sales_leads WHERE stage NOT IN ('won', 'lost') $emp_condition");
$pipelineValue = $stmt->fetch()['pipeline'] ?? 0;

// New leads this month
$stmt = $conn->query("SELECT COUNT(*) as new FROM sales_leads WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) $emp_condition");
$newLeads = $stmt->fetch()['new'];

// Conversion rate
$conversionRate = $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100) : 0;

// Lead trend (last 6 months)
$trend = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
for ($i = 5; $i >= 0; $i--) {
    $month = date('m', strtotime("-$i months"));
    $monthName = $months[(int)$month - 1];
    $year = date('Y', strtotime("-$i months"));
    $stmt = $conn->query("SELECT COUNT(*) as count FROM sales_leads WHERE MONTH(created_at) = $month AND YEAR(created_at) = $year $emp_condition");
    $trend['labels'][] = $monthName;
    $trend['values'][] = $stmt->fetch()['count'];
}

// Recent leads
$recentLeads = [];
$stmt = $conn->query("SELECT id, client_name, service_interest, stage, expected_amount, expected_close_date FROM sales_leads WHERE 1=1 $emp_condition ORDER BY created_at DESC LIMIT 5");
while ($row = $stmt->fetch()) {
    $recentLeads[] = $row;
}

// Get current month target
$currentMonth = date('m');
$currentYear = date('Y');
$stmt = $conn->prepare("SELECT target_amount FROM sales_targets WHERE sales_person_id = ? AND month = ? AND year = ?");
$stmt->execute([$employee_id, $currentMonth, $currentYear]);
$target = $stmt->fetch();
$revenueTarget = $target['target_amount'] ?? 0;

// Avg deal size
$avgDealSize = $wonLeads > 0 ? round($revenue / $wonLeads) : 0;

echo json_encode([
    'success' => true,
    'total_leads' => $totalLeads,
    'won_leads' => $wonLeads,
    'total_revenue' => $revenue,
    'pipeline_value' => $pipelineValue,
    'new_leads' => $newLeads,
    'conversion_rate' => $conversionRate,
    'revenue_target' => $revenueTarget,
    'avg_deal_size' => $avgDealSize,
    'lead_trend' => $trend,
    'recent_leads' => $recentLeads
]);
?>