<?php
// api/employee/get_leave_balance.php - Get employee leave balances
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

// Verify employee belongs to this user
$verify = mysqli_prepare($conn, "SELECT id, first_name, last_name, joining_date FROM employees WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($verify, "ii", $employee_id, $user_id);
mysqli_stmt_execute($verify);
$verify_result = mysqli_stmt_get_result($verify);
$employee = mysqli_fetch_assoc($verify_result);

if (!$employee) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}
mysqli_stmt_close($verify);

// Get leave types and balances
$query = "SELECT 
            lt.id,
            lt.leave_code,
            lt.leave_name,
            lt.days_per_year,
            lt.carry_forward,
            lt.max_carry_forward,
            COALESCE(lb.total_days, lt.days_per_year) as total_days,
            COALESCE(lb.used_days, 0) as used_days,
            COALESCE(lb.pending_days, 0) as pending_days,
            COALESCE(lb.carried_forward, 0) as carried_forward,
            (COALESCE(lb.total_days, lt.days_per_year) - COALESCE(lb.used_days, 0)) as remaining_days
        FROM leave_types lt
        LEFT JOIN leave_balances lb ON lt.id = lb.leave_type_id 
            AND lb.employee_id = ? AND lb.year = ?
        WHERE lt.status = 'active'
        ORDER BY lt.id";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $employee_id, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leave_balances = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Calculate total leaves taken this year
$taken_query = "SELECT SUM(total_days) as total_taken 
                FROM leave_requests 
                WHERE employee_id = ? 
                AND status = 'approved' 
                AND YEAR(from_date) = ?";
$taken_stmt = mysqli_prepare($conn, $taken_query);
mysqli_stmt_bind_param($taken_stmt, "ii", $employee_id, $year);
mysqli_stmt_execute($taken_stmt);
$taken_result = mysqli_stmt_get_result($taken_stmt);
$taken_data = mysqli_fetch_assoc($taken_result);
$total_leaves_taken = $taken_data['total_taken'] ?? 0;
mysqli_stmt_close($taken_stmt);

// Get pending leave requests count
$pending_query = "SELECT COUNT(*) as pending_count 
                  FROM leave_requests 
                  WHERE employee_id = ? AND status = 'pending'";
$pending_stmt = mysqli_prepare($conn, $pending_query);
mysqli_stmt_bind_param($pending_stmt, "i", $employee_id);
mysqli_stmt_execute($pending_stmt);
$pending_result = mysqli_stmt_get_result($pending_stmt);
$pending_data = mysqli_fetch_assoc($pending_result);
$pending_count = $pending_data['pending_count'] ?? 0;
mysqli_stmt_close($pending_stmt);

// Format response
$balances = [];
foreach ($leave_balances as $lb) {
    $balances[$lb['leave_code']] = [
        'id' => $lb['id'],
        'name' => $lb['leave_name'],
        'total' => (float)$lb['total_days'],
        'used' => (float)$lb['used_days'],
        'pending' => (float)$lb['pending_days'],
        'remaining' => (float)$lb['remaining_days'],
        'carried_forward' => (float)$lb['carried_forward'],
        'days_per_year' => (float)$lb['days_per_year']
    ];
}

echo json_encode([
    'success' => true,
    'balances' => $balances,
    'summary' => [
        'total_leaves_taken' => (float)$total_leaves_taken,
        'pending_requests' => $pending_count,
        'year' => $year,
        'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
        'joining_date' => $employee['joining_date']
    ]
]);

mysqli_close($conn);
?>