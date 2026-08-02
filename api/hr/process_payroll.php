<?php
// api/hr/process_payroll.php - Process monthly payroll for all employees
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

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

$month = isset($input['month']) ? (int)$input['month'] : date('m');
$year = isset($input['year']) ? (int)$input['year'] : date('Y');

// Validate month and year
if ($month < 1 || $month > 12) {
    echo json_encode(['success' => false, 'error' => 'Invalid month']);
    exit;
}

if ($year < 2000 || $year > 2100) {
    echo json_encode(['success' => false, 'error' => 'Invalid year']);
    exit;
}

// Check if payroll already exists for this month
$check_query = "SELECT COUNT(*) as count FROM payroll WHERE month = ? AND year = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "ii", $month, $year);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$check_data = mysqli_fetch_assoc($check_result);
$existing_count = $check_data['count'] ?? 0;
mysqli_stmt_close($check_stmt);

if ($existing_count > 0) {
    echo json_encode(['success' => false, 'error' => 'Payroll already processed for this month', 'existing_records' => $existing_count]);
    exit;
}

// Get all active employees with their salary components
$employees_query = "SELECT 
                    e.id as employee_id,
                    e.first_name,
                    e.last_name,
                    e.basic_salary,
                    e.hra,
                    e.special_allowance,
                    e.other_allowance,
                    e.bank_name,
                    e.bank_account_no,
                    COALESCE(
                        (SELECT SUM(total_days) FROM leave_requests 
                         WHERE employee_id = e.id 
                         AND status = 'approved' 
                         AND MONTH(from_date) = ? 
                         AND YEAR(from_date) = ?), 0
                    ) as leave_days_taken
                  FROM employees e
                  WHERE e.status = 'active'";

$emp_stmt = mysqli_prepare($conn, $employees_query);
mysqli_stmt_bind_param($emp_stmt, "ii", $month, $year);
mysqli_stmt_execute($emp_stmt);
$emp_result = mysqli_stmt_get_result($emp_stmt);
$employees = mysqli_fetch_all($emp_result, MYSQLI_ASSOC);
mysqli_stmt_close($emp_stmt);

if (empty($employees)) {
    echo json_encode(['success' => false, 'error' => 'No active employees found']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);
$processed_count = 0;
$payroll_records = [];

try {
    foreach ($employees as $emp) {
        // Calculate earnings
        $basic = (float)($emp['basic_salary'] ?? 0);
        $hra = (float)($emp['hra'] ?? 0);
        $special_allowance = (float)($emp['special_allowance'] ?? 0);
        $other_allowance = (float)($emp['other_allowance'] ?? 0);
        
        // Adjust for leave without pay (LOP)
        $lop_days = (float)$emp['leave_days_taken'];
        if ($lop_days > 0) {
            $daily_rate = $basic / 30;
            $lop_deduction = $daily_rate * $lop_days;
            $basic = max(0, $basic - $lop_deduction);
        }
        
        $total_earnings = $basic + $hra + $special_allowance + $other_allowance;
        
        // Calculate deductions
        $pf_deduction = round($basic * 0.12, 2); // 12% PF
        $professional_tax = ($total_earnings > 15000) ? 200 : 0;
        
        // TDS calculation (simplified - 5% of basic for > 5L annually)
        $annual_projected = $total_earnings * 12;
        $tds = ($annual_projected > 500000) ? round($total_earnings * 0.05, 2) : 0;
        
        $total_deductions = $pf_deduction + $professional_tax + $tds;
        $net_salary = $total_earnings - $total_deductions;
        
        // Insert payroll record
        $insert_query = "INSERT INTO payroll (
                            employee_id, month, year, payroll_date,
                            basic, hra, special_allowance, other_earnings, total_earnings,
                            pf_deduction, professional_tax, tds, total_deductions, net_salary,
                            payment_status, created_at
                        ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, 
            "iiidddddddddd", 
            $emp['employee_id'], $month, $year,
            $basic, $hra, $special_allowance, $other_allowance, $total_earnings,
            $pf_deduction, $professional_tax, $tds, $total_deductions, $net_salary
        );
        mysqli_stmt_execute($insert_stmt);
        $payroll_id = mysqli_insert_id($conn);
        mysqli_stmt_close($insert_stmt);
        
        if ($payroll_id) {
            $processed_count++;
            $payroll_records[] = [
                'employee_id' => $emp['employee_id'],
                'name' => $emp['first_name'] . ' ' . $emp['last_name'],
                'basic' => $basic,
                'net_salary' => $net_salary
            ];
        }
    }
    
    // Log activity
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $log_query = "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address) 
                  VALUES (?, 'payroll_processed', ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $description = "Processed payroll for $processed_count employees for " . date('F Y', mktime(0,0,0,$month,1,$year));
    mysqli_stmt_bind_param($log_stmt, "iss", $_SESSION['user_id'], $description, $ip_address);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => "Payroll processed successfully for " . date('F Y', mktime(0,0,0,$month,1,$year)),
        'processed_count' => $processed_count,
        'total_employees' => count($employees),
        'payroll_records' => $payroll_records,
        'next_step' => 'You can now view and mark payments in the Payroll section'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

mysqli_close($conn);
?>