<?php
// api/employee/get_attendance.php - Get employee attendance history
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
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

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

// Get attendance for the specified month
$start_date = "$year-$month-01";
$end_date = date('Y-m-t', strtotime($start_date));

$query = "SELECT 
            attendance_date,
            DATE_FORMAT(attendance_date, '%d %b %Y') as date_formatted,
            TIME_FORMAT(check_in_time, '%h:%i %p') as check_in_formatted,
            TIME_FORMAT(check_out_time, '%h:%i %p') as check_out_formatted,
            check_in_time,
            check_out_time,
            status,
            working_hours,
            late_minutes,
            early_exit_minutes,
            notes
          FROM attendance 
          WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?
          ORDER BY attendance_date DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iss", $employee_id, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$attendance = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Get monthly summary
$summary_query = "SELECT 
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_day,
                    SUM(working_hours) as total_hours,
                    AVG(late_minutes) as avg_late_minutes
                  FROM attendance 
                  WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?";

$stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($stmt, "iss", $employee_id, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Calculate attendance percentage
$total_working_days = $summary['total_days'] ?? 1;
$present_days = ($summary['present'] ?? 0) + ($summary['half_day'] ?? 0) * 0.5;
$attendance_percentage = round(($present_days / $total_working_days) * 100, 1);

// Get holidays in this month
$holiday_query = "SELECT holiday_date, holiday_name FROM holidays 
                  WHERE YEAR(holiday_date) = ? AND MONTH(holiday_date) = ?";
$stmt = mysqli_prepare($conn, $holiday_query);
mysqli_stmt_bind_param($stmt, "ii", $year, $month);
mysqli_stmt_execute($stmt);
$holidays_result = mysqli_stmt_get_result($stmt);
$holidays = mysqli_fetch_all($holidays_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'attendance' => $attendance,
    'summary' => [
        'total_days' => (int)($summary['total_days'] ?? 0),
        'present' => (int)($summary['present'] ?? 0),
        'absent' => (int)($summary['absent'] ?? 0),
        'late' => (int)($summary['late'] ?? 0),
        'half_day' => (int)($summary['half_day'] ?? 0),
        'total_hours' => round($summary['total_hours'] ?? 0, 1),
        'avg_late_minutes' => round($summary['avg_late_minutes'] ?? 0, 1),
        'attendance_percentage' => $attendance_percentage
    ],
    'holidays' => $holidays,
    'month' => $month,
    'year' => $year,
    'month_name' => date('F Y', strtotime($start_date))
]);

mysqli_close($conn);
?>