<?php
// api/employee/get_dashboard.php - Employee Dashboard Data API
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

// Verify employee belongs to this user
$verify = mysqli_prepare($conn, "SELECT id FROM employees WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($verify, "ii", $employee_id, $user_id);
mysqli_stmt_execute($verify);
$verify_result = mysqli_stmt_get_result($verify);

if (!mysqli_fetch_assoc($verify_result)) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}
mysqli_stmt_close($verify);

// Get attendance stats for current month
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$attendance_query = "SELECT 
    COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
    COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
    COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_day
FROM attendance 
WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?";

$stmt = mysqli_prepare($conn, $attendance_query);
mysqli_stmt_bind_param($stmt, "iss", $employee_id, $month_start, $month_end);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$days_present = ($stats['present'] ?? 0) + ($stats['half_day'] ?? 0) * 0.5;

// Get leaves taken this year
$leave_query = "SELECT SUM(total_days) as leaves_taken 
                FROM leave_requests 
                WHERE employee_id = ? AND status = 'approved' 
                AND YEAR(from_date) = YEAR(CURDATE())";
$stmt = mysqli_prepare($conn, $leave_query);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$leave_result = mysqli_stmt_get_result($stmt);
$leave_data = mysqli_fetch_assoc($leave_result);
$leaves_taken = $leave_data['leaves_taken'] ?? 0;
mysqli_stmt_close($stmt);

// Get employee salary
$salary_query = "SELECT basic_salary, hra, special_allowance, total_ctc 
                 FROM employees WHERE id = ?";
$stmt = mysqli_prepare($conn, $salary_query);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$salary_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Get today's attendance
$today = date('Y-m-d');
$today_query = "SELECT check_in_time, check_out_time, status, working_hours 
                FROM attendance 
                WHERE employee_id = ? AND attendance_date = ?";
$stmt = mysqli_prepare($conn, $today_query);
mysqli_stmt_bind_param($stmt, "is", $employee_id, $today);
mysqli_stmt_execute($stmt);
$today_attendance = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Get recent attendance (last 7 days)
$recent_query = "SELECT attendance_date, check_in_time, check_out_time, status, working_hours 
                 FROM attendance 
                 WHERE employee_id = ? 
                 ORDER BY attendance_date DESC LIMIT 7";
$stmt = mysqli_prepare($conn, $recent_query);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$recent_attendance = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Chart data - last 6 months attendance summary
$chart_labels = [];
$chart_values = [];
for ($i = 5; $i >= 0; $i--) {
    $month_name = date('M', strtotime("-$i months"));
    $chart_labels[] = $month_name;
    
    $month_start_chart = date('Y-m-01', strtotime("-$i months"));
    $month_end_chart = date('Y-m-t', strtotime("-$i months"));
    
    $month_query = "SELECT COUNT(*) as days FROM attendance 
                    WHERE employee_id = ? AND attendance_date BETWEEN ? AND ? 
                    AND status IN ('present', 'late', 'half_day')";
    $stmt = mysqli_prepare($conn, $month_query);
    mysqli_stmt_bind_param($stmt, "iss", $employee_id, $month_start_chart, $month_end_chart);
    mysqli_stmt_execute($stmt);
    $month_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $chart_values[] = $month_data['days'] ?? 0;
    mysqli_stmt_close($stmt);
}

echo json_encode([
    'success' => true,
    'days_present' => (int)$days_present,
    'days_absent' => (int)($stats['absent'] ?? 0),
    'leaves_taken' => (int)$leaves_taken,
    'salary' => (float)($salary_data['basic_salary'] ?? 0),
    'today_attendance' => $today_attendance,
    'recent_attendance' => $recent_attendance,
    'chart_data' => [
        'labels' => $chart_labels,
        'values' => $chart_values
    ]
]);

mysqli_close($conn);
?>