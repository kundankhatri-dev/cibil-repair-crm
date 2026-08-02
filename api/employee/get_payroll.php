<?php
// api/employee/get_payroll.php - Get employee salary and payroll history
session_start();
header('Content-Type: application/json');

// Allow only employees
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
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

$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
$user_id = $_SESSION['user_id'];
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

// Verify employee belongs to this user
$verify = mysqli_prepare($conn, "SELECT id, first_name, last_name, employee_code FROM employees WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($verify, "ii", $employee_id, $user_id);
mysqli_stmt_execute($verify);
$verify_result = mysqli_stmt_get_result($verify);
$employee = mysqli_fetch_assoc($verify_result);

if (!$employee) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}
mysqli_stmt_close($verify);

// Get current employee salary details
$salary_query = "SELECT 
                    e.basic_salary,
                    e.hra,
                    e.special_allowance,
                    e.other_allowance,
                    e.total_ctc,
                    e.bank_name,
                    e.bank_account_no,
                    e.ifsc_code,
                    e.uan_number,
                    d.department_name,
                    ds.designation_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations ds ON e.designation_id = ds.id
                WHERE e.id = ?";
$salary_stmt = mysqli_prepare($conn, $salary_query);
mysqli_stmt_bind_param($salary_stmt, "i", $employee_id);
mysqli_stmt_execute($salary_stmt);
$salary_result = mysqli_stmt_get_result($salary_stmt);
$current_salary = mysqli_fetch_assoc($salary_result);
mysqli_stmt_close($salary_stmt);

// Get payroll history
$payroll_query = "SELECT 
                    p.id,
                    p.month,
                    p.year,
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
                    DATE_FORMAT(p.payment_date, '%d %b %Y') as payment_date_formatted,
                    p.payment_status,
                    p.notes
                FROM payroll p
                WHERE p.employee_id = ?
                ORDER BY p.year DESC, p.month DESC
                LIMIT 12";

$payroll_stmt = mysqli_prepare($conn, $payroll_query);
mysqli_stmt_bind_param($payroll_stmt, "i", $employee_id);
mysqli_stmt_execute($payroll_stmt);
$payroll_result = mysqli_stmt_get_result($payroll_stmt);
$payroll_history = mysqli_fetch_all($payroll_result, MYSQLI_ASSOC);
mysqli_stmt_close($payroll_stmt);

// Get current month payroll (if exists)
$current_payroll = null;
$current_query = "SELECT 
                    p.*,
                    DATE_FORMAT(p.payroll_date, '%d %b %Y') as payroll_date_formatted
                  FROM payroll p
                  WHERE p.employee_id = ? AND p.month = ? AND p.year = ?";
$current_stmt = mysqli_prepare($conn, $current_query);
mysqli_stmt_bind_param($current_stmt, "iii", $employee_id, $month, $year);
mysqli_stmt_execute($current_stmt);
$current_result = mysqli_stmt_get_result($current_stmt);
$current_payroll = mysqli_fetch_assoc($current_result);
mysqli_stmt_close($current_stmt);

// Calculate total earnings and deductions for year
$yearly_query = "SELECT 
                    SUM(total_earnings) as total_earnings,
                    SUM(total_deductions) as total_deductions,
                    SUM(net_salary) as total_net_salary,
                    SUM(pf_deduction) as total_pf,
                    SUM(tds) as total_tds
                FROM payroll
                WHERE employee_id = ? AND year = ? AND payment_status = 'processed'";
$yearly_stmt = mysqli_prepare($conn, $yearly_query);
mysqli_stmt_bind_param($yearly_stmt, "ii", $employee_id, $year);
mysqli_stmt_execute($yearly_stmt);
$yearly_result = mysqli_stmt_get_result($yearly_stmt);
$yearly_totals = mysqli_fetch_assoc($yearly_result);
mysqli_stmt_close($yearly_stmt);

// Format payroll history
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
foreach ($payroll_history as &$p) {
    $p['month_name'] = $months[$p['month'] - 1];
    $p['net_salary_formatted'] = '₹' . number_format($p['net_salary'], 2);
    $p['basic_formatted'] = '₹' . number_format($p['basic'] ?? 0, 2);
    $p['hra_formatted'] = '₹' . number_format($p['hra'] ?? 0, 2);
    
    $status_class = [
        'processed' => 'success',
        'pending' => 'warning',
        'failed' => 'danger'
    ][$p['payment_status']] ?? 'secondary';
    $p['status_badge'] = $status_class;
}

// Get salary components for current employee
$total_allowances = ($current_salary['hra'] ?? 0) + ($current_salary['special_allowance'] ?? 0) + ($current_salary['other_allowance'] ?? 0);
$standard_deductions = 0; // Will be calculated based on actual payroll

echo json_encode([
    'success' => true,
    'employee' => [
        'id' => $employee['id'],
        'code' => $employee['employee_code'],
        'name' => $employee['first_name'] . ' ' . $employee['last_name'],
        'department' => $current_salary['department_name'] ?? '',
        'designation' => $current_salary['designation_name'] ?? ''
    ],
    'current' => [
        'basic' => (float)($current_salary['basic_salary'] ?? 0),
        'hra' => (float)($current_salary['hra'] ?? 0),
        'special_allowance' => (float)($current_salary['special_allowance'] ?? 0),
        'other_allowance' => (float)($current_salary['other_allowance'] ?? 0),
        'total_allowances' => $total_allowances,
        'total_earnings' => (float)($current_salary['basic_salary'] ?? 0) + $total_allowances,
        'total_ctc' => (float)($current_salary['total_ctc'] ?? 0),
        'bank_name' => $current_salary['bank_name'] ?? '',
        'account_number' => $current_salary['bank_account_no'] ? 'XXXX' . substr($current_salary['bank_account_no'], -4) : '',
        'ifsc_code' => $current_salary['ifsc_code'] ?? '',
        'uan_number' => $current_salary['uan_number'] ?? ''
    ],
    'current_month_payroll' => $current_payroll ? [
        'id' => $current_payroll['id'],
        'month' => $months[$current_payroll['month'] - 1],
        'year' => $current_payroll['year'],
        'basic' => (float)($current_payroll['basic'] ?? 0),
        'hra' => (float)($current_payroll['hra'] ?? 0),
        'special_allowance' => (float)($current_payroll['special_allowance'] ?? 0),
        'total_earnings' => (float)($current_payroll['total_earnings'] ?? 0),
        'pf_deduction' => (float)($current_payroll['pf_deduction'] ?? 0),
        'professional_tax' => (float)($current_payroll['professional_tax'] ?? 0),
        'tds' => (float)($current_payroll['tds'] ?? 0),
        'total_deductions' => (float)($current_payroll['total_deductions'] ?? 0),
        'net_salary' => (float)($current_payroll['net_salary'] ?? 0),
        'net_salary_formatted' => '₹' . number_format($current_payroll['net_salary'] ?? 0, 2),
        'payment_status' => $current_payroll['payment_status'] ?? 'pending',
        'payment_date' => $current_payroll['payment_date_formatted'] ?? 'Not processed'
    ] : null,
    'history' => $payroll_history,
    'yearly_summary' => [
        'year' => $year,
        'total_earnings' => (float)($yearly_totals['total_earnings'] ?? 0),
        'total_earnings_formatted' => '₹' . number_format($yearly_totals['total_earnings'] ?? 0, 2),
        'total_deductions' => (float)($yearly_totals['total_deductions'] ?? 0),
        'total_deductions_formatted' => '₹' . number_format($yearly_totals['total_deductions'] ?? 0, 2),
        'total_net_salary' => (float)($yearly_totals['total_net_salary'] ?? 0),
        'total_net_salary_formatted' => '₹' . number_format($yearly_totals['total_net_salary'] ?? 0, 2),
        'total_pf' => (float)($yearly_totals['total_pf'] ?? 0),
        'total_tds' => (float)($yearly_totals['total_tds'] ?? 0)
    ],
    'salary_breakdown' => [
        'earnings' => [
            ['name' => 'Basic Salary', 'amount' => (float)($current_salary['basic_salary'] ?? 0), 'percentage' => 100],
            ['name' => 'HRA', 'amount' => (float)($current_salary['hra'] ?? 0), 'percentage' => round(($current_salary['hra'] ?? 0) / max(1, ($current_salary['basic_salary'] ?? 1)) * 100, 1)],
            ['name' => 'Special Allowance', 'amount' => (float)($current_salary['special_allowance'] ?? 0), 'percentage' => round(($current_salary['special_allowance'] ?? 0) / max(1, ($current_salary['basic_salary'] ?? 1)) * 100, 1)]
        ],
        'deductions' => [
            ['name' => 'PF (12%)', 'amount' => round(($current_salary['basic_salary'] ?? 0) * 0.12, 2)],
            ['name' => 'Professional Tax', 'amount' => 200],
            ['name' => 'TDS', 'amount' => round(($current_salary['basic_salary'] ?? 0) * 0.05, 2)]
        ]
    ]
]);

mysqli_close($conn);
?>