<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['operations_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
if (!$conn) { echo json_encode(['success' => false, 'error' => 'DB failed']); exit; }

// Create cases table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS operation_cases (
    id INT PRIMARY KEY AUTO_INCREMENT, case_no VARCHAR(50), client_id INT NOT NULL,
    service VARCHAR(100), assigned_to INT, status ENUM('pending','in_progress','completed','closed') DEFAULT 'pending',
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium', sla_due DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, completed_at DATETIME
)");

// Create case_assignments table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS case_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT, case_id INT, assigned_to INT, assigned_by INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, notes TEXT
)");

// Create tasks table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS operation_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT, title VARCHAR(255), assigned_to INT,
    priority ENUM('low','medium','high') DEFAULT 'medium', status ENUM('todo','in_progress','completed') DEFAULT 'todo',
    due_date DATE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, completed_at DATETIME
)");

// Create daily_reports table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS daily_operations_reports (
    id INT PRIMARY KEY AUTO_INCREMENT, report_date DATE, cases_opened INT,
    cases_closed INT, avg_resolution_days DECIMAL(5,2), sla_met_percent INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$total_cases = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE status NOT IN ('completed','closed')"))['c'] ?? 0;
$active_employees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role IN ('employee','credit_analyst','support_team') AND status='active'"))['c'] ?? 0;
$sla_breached = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE status NOT IN ('completed','closed') AND NOW() > sla_due"))['c'] ?? 0;
$cases_resolved_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE status='completed' AND MONTH(completed_at) = MONTH(CURDATE())"))['c'] ?? 0;

// Case trend (last 6 months)
$trend_labels = []; $trend_values = [];
for ($i = 5; $i >= 0; $i--) {
    $trend_labels[] = date('M', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE created_at BETWEEN '$start' AND '$end'"))['c'] ?? 0;
    $trend_values[] = $cnt;
}

// Department distribution
$dept_labels = ['Credit Analyst', 'Dispute Team', 'Support', 'Operations'];
$dept_values = [
    mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='credit_analyst')"))['c'] ?? 0,
    mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='dispute_team')"))['c'] ?? 0,
    mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='support_team')"))['c'] ?? 0,
    mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE assigned_to IN (SELECT id FROM users WHERE role='operations_team')"))['c'] ?? 0
];

// Recent cases
$recent = mysqli_query($conn, "SELECT c.*, u.name as client_name, 
          CASE WHEN NOW() > c.sla_due AND c.status NOT IN ('completed','closed') THEN 'Breached'
               WHEN TIMESTAMPDIFF(HOUR, NOW(), c.sla_due) <= 24 AND c.status NOT IN ('completed','closed') THEN 'At Risk'
               ELSE 'On Track' END as sla_status,
          CASE WHEN NOW() > c.sla_due AND c.status NOT IN ('completed','closed') THEN 'sla-critical'
               WHEN TIMESTAMPDIFF(HOUR, NOW(), c.sla_due) <= 24 AND c.status NOT IN ('completed','closed') THEN 'sla-warning'
               ELSE 'sla-good' END as sla_class
          FROM operation_cases c JOIN users u ON c.client_id = u.id ORDER BY c.created_at DESC LIMIT 10");
$recent_cases = [];
while ($row = mysqli_fetch_assoc($recent)) {
    $row['assigned_to'] = $row['assigned_to'] ? getEmployeeName($conn, $row['assigned_to']) : 'Unassigned';
    $recent_cases[] = $row;
}

echo json_encode([
    'success' => true, 'total_cases' => $total_cases, 'active_employees' => $active_employees,
    'sla_breached' => $sla_breached, 'cases_resolved_month' => $cases_resolved_month,
    'case_trend' => ['labels' => $trend_labels, 'values' => $trend_values],
    'dept_distribution' => ['labels' => $dept_labels, 'values' => $dept_values],
    'recent_cases' => $recent_cases
]);
mysqli_close($conn);

function getEmployeeName($conn, $id) {
    $q = mysqli_query($conn, "SELECT name FROM users WHERE id = $id");
    return ($r = mysqli_fetch_assoc($q)) ? $r['name'] : 'Unknown';
}
?>