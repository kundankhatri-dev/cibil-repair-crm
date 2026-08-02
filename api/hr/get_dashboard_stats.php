<?php
// api/hr/get_dashboard_stats.php - HR Dashboard Statistics API
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

// Get current user's employee ID if HR
$hr_employee_id = 0;
if ($_SESSION['user_role'] === 'hr') {
    $hr_query = "SELECT id FROM employees WHERE user_id = ?";
    $hr_stmt = mysqli_prepare($conn, $hr_query);
    mysqli_stmt_bind_param($hr_stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($hr_stmt);
    $hr_result = mysqli_stmt_get_result($hr_stmt);
    $hr_data = mysqli_fetch_assoc($hr_result);
    $hr_employee_id = $hr_data['id'] ?? 0;
    mysqli_stmt_close($hr_stmt);
}

// 1. Total Employees
$emp_query = "SELECT COUNT(*) as total FROM employees WHERE status = 'active'";
$emp_result = mysqli_query($conn, $emp_query);
$total_employees = mysqli_fetch_assoc($emp_result)['total'] ?? 0;

// 2. New employees this month
$new_emp_query = "SELECT COUNT(*) as new_count FROM employees 
                  WHERE MONTH(joining_date) = MONTH(CURDATE()) 
                  AND YEAR(joining_date) = YEAR(CURDATE())";
$new_emp_result = mysqli_query($conn, $new_emp_query);
$new_employees = mysqli_fetch_assoc($new_emp_result)['new_count'] ?? 0;

// 3. Present today
$today = date('Y-m-d');
$present_query = "SELECT COUNT(DISTINCT a.employee_id) as present 
                  FROM attendance a 
                  WHERE a.attendance_date = ? AND a.status IN ('present', 'late', 'half_day')";
$present_stmt = mysqli_prepare($conn, $present_query);
mysqli_stmt_bind_param($present_stmt, "s", $today);
mysqli_stmt_execute($present_stmt);
$present_result = mysqli_stmt_get_result($present_stmt);
$present_today = mysqli_fetch_assoc($present_result)['present'] ?? 0;
mysqli_stmt_close($present_stmt);

// 4. Attendance rate (this month so far)
$month_start = date('Y-m-01');
$working_days = 0;
$working_days_query = "SELECT COUNT(DISTINCT attendance_date) as days 
                       FROM attendance 
                       WHERE attendance_date BETWEEN '$month_start' AND '$today'";
$wd_result = mysqli_query($conn, $working_days_query);
$working_days = mysqli_fetch_assoc($wd_result)['days'] ?? 1;

$attendance_rate = ($working_days > 0 && $total_employees > 0) 
    ? round(($present_today / ($total_employees * $working_days)) * 100, 1) 
    : 0;

// 5. Pending leaves
$pending_query = "SELECT COUNT(*) as pending FROM leave_requests WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_leaves = mysqli_fetch_assoc($pending_result)['pending'] ?? 0;

// 6. Monthly payroll
$payroll_query = "SELECT SUM(net_salary) as total_payroll 
                  FROM payroll 
                  WHERE month = MONTH(CURDATE()) AND year = YEAR(CURDATE())";
$payroll_result = mysqli_query($conn, $payroll_query);
$monthly_payroll = mysqli_fetch_assoc($payroll_result)['total_payroll'] ?? 0;

// 7. Payroll status
$payroll_status_query = "SELECT COUNT(*) as processed 
                         FROM payroll 
                         WHERE month = MONTH(CURDATE()) AND year = YEAR(CURDATE()) 
                         AND payment_status = 'processed'";
$ps_result = mysqli_query($conn, $payroll_status_query);
$processed_count = mysqli_fetch_assoc($ps_result)['processed'] ?? 0;
$payroll_status = ($processed_count >= $total_employees) ? 'Completed' : ($processed_count > 0 ? 'Partial' : 'Pending');

// 8. Today's attendance details
$today_attendance_query = "SELECT 
                            e.id, e.employee_code, 
                            CONCAT(e.first_name, ' ', e.last_name) as name,
                            d.department_name as department,
                            TIME_FORMAT(a.check_in_time, '%h:%i %p') as check_in_time,
                            TIME_FORMAT(a.check_out_time, '%h:%i %p') as check_out_time,
                            a.status, a.late_minutes
                          FROM employees e
                          LEFT JOIN attendance a ON e.id = a.employee_id AND a.attendance_date = ?
                          LEFT JOIN departments d ON e.department_id = d.id
                          WHERE e.status = 'active'
                          ORDER BY e.first_name";
$today_stmt = mysqli_prepare($conn, $today_attendance_query);
mysqli_stmt_bind_param($today_stmt, "s", $today);
mysqli_stmt_execute($today_stmt);
$today_result = mysqli_stmt_get_result($today_stmt);
$today_attendance = mysqli_fetch_all($today_result, MYSQLI_ASSOC);
mysqli_stmt_close($today_stmt);

// Format today's attendance
$formatted_attendance = [];
foreach ($today_attendance as $ta) {
    $formatted_attendance[] = [
        'id' => $ta['id'],
        'code' => $ta['employee_code'],
        'name' => $ta['name'],
        'department' => $ta['department'],
        'check_in_time' => $ta['check_in_time'] ?? '-',
        'check_out_time' => $ta['check_out_time'] ?? '-',
        'status' => $ta['status'] ?? 'absent',
        'late_minutes' => $ta['late_minutes'] ?? 0
    ];
}

// 9. Recent activities
$activities_query = "SELECT 
                      DATE_FORMAT(created_at, '%h:%i %p') as time,
                      CONCAT(e.first_name, ' ', e.last_name) as employee,
                      description as activity
                    FROM client_activity_log al
                    JOIN users u ON al.client_id = u.id
                    LEFT JOIN employees e ON u.id = e.user_id
                    WHERE al.activity_type IN ('attendance_marked', 'leave_requested', 'leave_approved')
                    ORDER BY al.created_at DESC LIMIT 10";
$activities_result = mysqli_query($conn, $activities_query);
$recent_activities = mysqli_fetch_all($activities_result, MYSQLI_ASSOC);

// 10. Attendance trend (last 6 months)
$trend_labels = [];
$trend_values = [];
for ($i = 5; $i >= 0; $i--) {
    $month_name = date('M', strtotime("-$i months"));
    $trend_labels[] = $month_name;
    
    $month_start_trend = date('Y-m-01', strtotime("-$i months"));
    $month_end_trend = date('Y-m-t', strtotime("-$i months"));
    
    $trend_query = "SELECT COUNT(DISTINCT a.employee_id) as days_present 
                    FROM attendance a
                    WHERE a.attendance_date BETWEEN ? AND ?
                    AND a.status IN ('present', 'late', 'half_day')";
    $trend_stmt = mysqli_prepare($conn, $trend_query);
    mysqli_stmt_bind_param($trend_stmt, "ss", $month_start_trend, $month_end_trend);
    mysqli_stmt_execute($trend_stmt);
    $trend_result = mysqli_stmt_get_result($trend_stmt);
    $trend_data = mysqli_fetch_assoc($trend_result);
    $trend_values[] = $trend_data['days_present'] ?? 0;
    mysqli_stmt_close($trend_stmt);
}

// 11. Department distribution
$dept_query = "SELECT d.department_name, COUNT(e.id) as count 
               FROM departments d
               LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
               GROUP BY d.id";
$dept_result = mysqli_query($conn, $dept_query);
$dept_data = mysqli_fetch_all($dept_result, MYSQLI_ASSOC);
$dept_labels = array_column($dept_data, 'department_name');
$dept_values = array_column($dept_data, 'count');

echo json_encode([
    'success' => true,
    'total_employees' => (int)$total_employees,
    'new_employees' => (int)$new_employees,
    'present_today' => (int)$present_today,
    'attendance_rate' => $attendance_rate,
    'pending_leaves' => (int)$pending_leaves,
    'monthly_payroll' => (float)$monthly_payroll,
    'payroll_status' => $payroll_status,
    'today_attendance' => $formatted_attendance,
    'recent_activities' => $recent_activities,
    'attendance_trend' => [
        'labels' => $trend_labels,
        'values' => $trend_values
    ],
    'dept_distribution' => [
        'labels' => $dept_labels,
        'values' => $dept_values
    ]
]);

mysqli_close($conn);
?>