<?php
// api/sales/get_performance.php
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

// Monthly performance (last 12 months)
$monthly_performance = [];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

for ($i = 11; $i >= 0; $i--) {
    $month = date('m', strtotime("-$i months"));
    $monthName = $months[(int)$month - 1];
    $year = date('Y', strtotime("-$i months"));
    
    // Get revenue from won deals
    $stmt = $conn->prepare("SELECT COALESCE(SUM(expected_amount), 0) as revenue 
        FROM sales_leads 
        WHERE stage = 'won' 
        AND MONTH(created_at) = ? 
        AND YEAR(created_at) = ? 
        $emp_condition");
    $stmt->execute([$month, $year]);
    $revenue = $stmt->fetch();
    
    $monthly_performance['labels'][] = $monthName;
    $monthly_performance['revenue'][] = $revenue['revenue'] ?? 0;
}

// Top performing lead sources
$stmt = $conn->prepare("SELECT source, COUNT(*) as count, COALESCE(SUM(expected_amount), 0) as total_value 
    FROM sales_leads 
    WHERE 1=1 $emp_condition 
    GROUP BY source 
    ORDER BY total_value DESC 
    LIMIT 5");
$stmt->execute();
$top_sources = $stmt->fetchAll();

// Conversion by stage
$stmt = $conn->prepare("SELECT stage, COUNT(*) as count 
    FROM sales_leads 
    WHERE 1=1 $emp_condition 
    GROUP BY stage");
$stmt->execute();
$stage_distribution = $stmt->fetchAll();

// Average deal size by service
$stmt = $conn->prepare("SELECT service_interest, COUNT(*) as count, AVG(expected_amount) as avg_amount, SUM(expected_amount) as total 
    FROM sales_leads 
    WHERE stage = 'won' AND 1=1 $emp_condition 
    GROUP BY service_interest 
    ORDER BY total DESC");
$stmt->execute();
$service_performance = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'monthly_performance' => $monthly_performance,
    'top_sources' => $top_sources,
    'stage_distribution' => $stage_distribution,
    'service_performance' => $service_performance
]);
?>