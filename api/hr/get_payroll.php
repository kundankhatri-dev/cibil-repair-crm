<?php
// api/hr/get_payroll.php - Get payroll data for HR
session_start();
header('Content-Type: application/json');

// Allow only HR or Admin
$allowed_roles = ['hr', 'admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get filter parameters
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build query
$query = "SELECT 
            p.id,
            p.employee_id,
            p.month,
            p.year,
            p.payroll_date,
            DATE_FORMAT(p.payroll_date, '%d %b %Y') as payroll_date_formatted,
            p.basic,
            p.hra,
            p.special_allowance,
            p.other_earnings,
            p.total_earnings,
            p.pf_deduction,
            p.esi_deduction,
            p.professional_tax,
            p.tds,
            p.loan_deduction,
            p.advance_deduction,
            p.other_deductions,
            p.total_deductions,
            p.net_salary,
            p.payment_mode,
            p.transaction_id,
            p.payment_date,
            DATE_FORMAT(p.payment_date, '%d %b %Y') as payment_date_formatted,
            p.payment_status,
            p.notes,
            -- Employee details
            CONCAT(e.first_name, ' ', e.last_name) as employee_name,
            e.employee_code,
            e.work_email,
            e.personal_phone,
            e.bank_name,
            e.bank_account_no,
            e.ifsc_code,
            e.uan_number,
            -- Department
            d.department_name,
            -- Designation
            ds.designation_name
          FROM payroll p
          JOIN employees e ON p.employee_id = e.id
          LEFT JOIN departments d ON e.department_id = d.id
          LEFT JOIN designations ds ON e.designation_id = ds.id
          WHERE p.month = ? AND p.year = ?";

$params = [$month, $year];
$types = "ii";

if ($department_id > 0) {
    $query .= " AND e.department_id = ?";
    $params[] = $department_id;
    $types .= "i";
}

if (!empty($status)) {
    $query .= " AND p.payment_status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " ORDER BY e.first_name";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payroll = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Get summary totals
$summary_query = "SELECT 
                    COUNT(*) as employee_count,
                    SUM(p.basic) as total_basic,
                    SUM(p.hra) as total_hra,
                    SUM(p.special_allowance) as total_allowances,
                    SUM(p.total_earnings) as total_earnings,
                    SUM(p.total_deductions) as total_deductions,
                    SUM(p.net_salary) as total_net_salary,
                    SUM(CASE WHEN p.payment_status = 'processed' THEN p.net_salary ELSE 0 END) as total_paid,
                    SUM(CASE WHEN p.payment_status = 'pending' THEN p.net_salary ELSE 0 END) as total_pending
                  FROM payroll p
                  WHERE p.month = ? AND p.year = ?";

$summary_stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($summary_stmt, "ii", $month, $year);
mysqli_stmt_execute($summary_stmt);
$summary_result = mysqli_stmt_get_result($summary_stmt);
$summary = mysqli_fetch_assoc($summary_result);
mysqli_stmt_close($summary_stmt);

// Get departments for filter
$depts_query = "SELECT id, department_name FROM departments WHERE status = 'active'";
$depts_result = mysqli_query($conn, $depts_query);
$departments = mysqli_fetch_all($depts_result, MYSQLI_ASSOC);

// Format payroll data
foreach ($payroll as &$p) {
    $p['basic_formatted'] = '₹' . number_format($p['basic'] ?? 0, 2);
    $p['hra_formatted'] = '₹' . number_format($p['hra'] ?? 0, 2);
    $p['total_earnings_formatted'] = '₹' . number_format($p['total_earnings'] ?? 0, 2);
    $p['total_deductions_formatted'] = '₹' . number_format($p['total_deductions'] ?? 0, 2);
    $p['net_salary_formatted'] = '₹' . number_format($p['net_salary'] ?? 0, 2);
    
    // Account number masked
    $p['account_no_masked'] = $p['bank_account_no'] ? 'XXXX' . substr($p['bank_account_no'], -4) : '';
    
    // Status badge
    $status_class = [
        'processed' => 'success',
        'pending' => 'warning',
        'failed' => 'danger'
    ][$p['payment_status']] ?? 'secondary';
    $p['status_badge'] = $status_class;
}

// Check if payroll has been processed for this month
$processed_check = "SELECT COUNT(*) as count FROM payroll WHERE month = ? AND year = ?";
$check_stmt = mysqli_prepare($conn, $processed_check);
mysqli_stmt_bind_param($check_stmt, "ii", $month, $year);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$check_data = mysqli_fetch_assoc($check_result);
$has_payroll = ($check_data['count'] ?? 0) > 0;
mysqli_stmt_close($check_stmt);

// Month name
$month_names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$month_name = $month_names[$month - 1] . ' ' . $year;

echo json_encode([
    'success' => true,
    'payroll' => $payroll,
    'summary' => [
        'employee_count' => (int)($summary['employee_count'] ?? 0),
        'total_basic' => (float)($summary['total_basic'] ?? 0),
        'total_basic_formatted' => '₹' . number_format($summary['total_basic'] ?? 0, 2),
        'total_hra' => (float)($summary['total_hra'] ?? 0),
        'total_allowances' => (float)($summary['total_allowances'] ?? 0),
        'total_earnings' => (float)($summary['total_earnings'] ?? 0),
        'total_earnings_formatted' => '₹' . number_format($summary['total_earnings'] ?? 0, 2),
        'total_deductions' => (float)($summary['total_deductions'] ?? 0),
        'total_deductions_formatted' => '₹' . number_format($summary['total_deductions'] ?? 0, 2),
        'total_net_salary' => (float)($summary['total_net_salary'] ?? 0),
        'total_net_salary_formatted' => '₹' . number_format($summary['total_net_salary'] ?? 0, 2),
        'total_paid' => (float)($summary['total_paid'] ?? 0),
        'total_paid_formatted' => '₹' . number_format($summary['total_paid'] ?? 0, 2),
        'total_pending' => (float)($summary['total_pending'] ?? 0),
        'total_pending_formatted' => '₹' . number_format($summary['total_pending'] ?? 0, 2)
    ],
    'departments' => $departments,
    'has_payroll' => $has_payroll,
    'month' => $month,
    'year' => $year,
    'month_name' => $month_name,
    'filters' => [
        'month' => $month,
        'year' => $year,
        'department_id' => $department_id,
        'status' => $status
    ]
]);

mysqli_close($conn);
?>