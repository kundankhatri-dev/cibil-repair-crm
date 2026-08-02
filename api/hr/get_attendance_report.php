<?php
// api/hr/get_attendance_report.php - Get attendance report for HR
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
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

// Parse month and year
$year = substr($month, 0, 4);
$month_num = substr($month, 5, 2);
$month_start = "$year-$month_num-01";
$month_end = date('Y-m-t', strtotime($month_start));

// Get working days in this month (excluding Sundays - optional)
$working_days = 0;
$temp_date = strtotime($month_start);
while ($temp_date <= strtotime($month_end)) {
    $day_of_week = date('N', $temp_date);
    // Exclude Sundays (7) - adjust as needed
    if ($day_of_week < 6) {
        $working_days++;
    }
    $temp_date = strtotime('+1 day', $temp_date);
}

// Build employees query
$emp_query = "SELECT 
                e.id,
                e.employee_code,
                CONCAT(e.first_name, ' ', e.last_name) as name,
                d.department_name,
                ds.designation_name,
                e.joining_date
              FROM employees e
              LEFT JOIN departments d ON e.department_id = d.id
              LEFT JOIN designations ds ON e.designation_id = ds.id
              WHERE e.status = 'active'";

$params = [];
$types = "";

if ($department_id > 0) {
    $emp_query .= " AND e.department_id = ?";
    $params[] = $department_id;
    $types .= "i";
}

if ($employee_id > 0) {
    $emp_query .= " AND e.id = ?";
    $params[] = $employee_id;
    $types .= "i";
}

$emp_query .= " ORDER BY e.first_name";

$emp_stmt = mysqli_prepare($conn, $emp_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($emp_stmt, $types, ...$params);
}
mysqli_stmt_execute($emp_stmt);
$emp_result = mysqli_stmt_get_result($emp_stmt);
$employees = mysqli_fetch_all($emp_result, MYSQLI_ASSOC);
mysqli_stmt_close($emp_stmt);

// Get attendance for each employee
$report = [];
$total_present = 0;
$total_absent = 0;
$total_late = 0;
$total_half_day = 0;

foreach ($employees as $emp) {
    $attendance_query = "SELECT 
                            COUNT(*) as total_days,
                            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                            SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_day,
                            SUM(working_hours) as total_hours,
                            AVG(late_minutes) as avg_late_minutes
                        FROM attendance 
                        WHERE employee_id = ? 
                        AND attendance_date BETWEEN ? AND ?";
    
    $att_stmt = mysqli_prepare($conn, $attendance_query);
    mysqli_stmt_bind_param($att_stmt, "iss", $emp['id'], $month_start, $month_end);
    mysqli_stmt_execute($att_stmt);
    $att_result = mysqli_stmt_get_result($att_stmt);
    $att_data = mysqli_fetch_assoc($att_result);
    mysqli_stmt_close($att_stmt);
    
    // Calculate present days including half day as 0.5
    $present_days = ($att_data['present'] ?? 0) + ($att_data['half_day'] ?? 0) * 0.5;
    $attendance_percentage = ($working_days > 0) ? round(($present_days / $working_days) * 100, 1) : 0;
    
    // Get leave days in this month
    $leave_query = "SELECT SUM(total_days) as leave_days 
                    FROM leave_requests 
                    WHERE employee_id = ? 
                    AND status = 'approved'
                    AND ((from_date BETWEEN ? AND ?) OR (to_date BETWEEN ? AND ?))";
    $leave_stmt = mysqli_prepare($conn, $leave_query);
    mysqli_stmt_bind_param($leave_stmt, "issss", $emp['id'], $month_start, $month_end, $month_start, $month_end);
    mysqli_stmt_execute($leave_stmt);
    $leave_result = mysqli_stmt_get_result($leave_stmt);
    $leave_data = mysqli_fetch_assoc($leave_result);
    $leave_days = $leave_data['leave_days'] ?? 0;
    mysqli_stmt_close($leave_stmt);
    
    $report[] = [
        'employee_id' => $emp['id'],
        'employee_code' => $emp['employee_code'],
        'name' => $emp['name'],
        'department' => $emp['department_name'],
        'designation' => $emp['designation_name'],
        'present' => (int)($att_data['present'] ?? 0),
        'absent' => (int)($att_data['absent'] ?? 0),
        'late' => (int)($att_data['late'] ?? 0),
        'half_day' => (int)($att_data['half_day'] ?? 0),
        'total_hours' => round($att_data['total_hours'] ?? 0, 1),
        'avg_late_minutes' => round($att_data['avg_late_minutes'] ?? 0, 1),
        'leave_days' => (float)$leave_days,
        'working_days' => $working_days,
        'attendance_percentage' => $attendance_percentage
    ];
    
    // Accumulate totals
    $total_present += ($att_data['present'] ?? 0);
    $total_absent += ($att_data['absent'] ?? 0);
    $total_late += ($att_data['late'] ?? 0);
    $total_half_day += ($att_data['half_day'] ?? 0);
}

// Get departments for filter
$depts_query = "SELECT id, department_name FROM departments WHERE status = 'active'";
$depts_result = mysqli_query($conn, $depts_query);
$departments = mysqli_fetch_all($depts_result, MYSQLI_ASSOC);

// Get overall company attendance summary
$overall_present_percentage = (count($employees) > 0 && $working_days > 0) 
    ? round(($total_present / (count($employees) * $working_days)) * 100, 1) 
    : 0;

// Get daily attendance trend for the month
$daily_trend = [];
$temp_date = strtotime($month_start);
while ($temp_date <= strtotime($month_end)) {
    $date = date('Y-m-d', $temp_date);
    $day_name = date('D, d M', $temp_date);
    
    $daily_query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status IN ('present', 'late', 'half_day') THEN 1 ELSE 0 END) as present_count
                    FROM attendance a
                    WHERE a.attendance_date = ?";
    $daily_stmt = mysqli_prepare($conn, $daily_query);
    mysqli_stmt_bind_param($daily_stmt, "s", $date);
    mysqli_stmt_execute($daily_stmt);
    $daily_result = mysqli_stmt_get_result($daily_stmt);
    $daily_data = mysqli_fetch_assoc($daily_result);
    
    $daily_trend[] = [
        'date' => $date,
        'day' => $day_name,
        'present' => (int)($daily_data['present_count'] ?? 0),
        'total' => count($employees),
        'percentage' => count($employees) > 0 ? round((($daily_data['present_count'] ?? 0) / count($employees)) * 100, 1) : 0
    ];
    mysqli_stmt_close($daily_stmt);
    
    $temp_date = strtotime('+1 day', $temp_date);
}

// Month name
$month_names = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$month_name = $month_names[(int)$month_num - 1] . ' ' . $year;

echo json_encode([
    'success' => true,
    'report' => $report,
    'summary' => [
        'total_employees' => count($employees),
        'total_present' => $total_present,
        'total_absent' => $total_absent,
        'total_late' => $total_late,
        'total_half_day' => $total_half_day,
        'working_days' => $working_days,
        'overall_attendance_percentage' => $overall_present_percentage
    ],
    'daily_trend' => $daily_trend,
    'departments' => $departments,
    'month' => $month,
    'year' => $year,
    'month_num' => (int)$month_num,
    'month_name' => $month_name,
    'filters' => [
        'department_id' => $department_id,
        'employee_id' => $employee_id
    ]
]);

mysqli_close($conn);
?>