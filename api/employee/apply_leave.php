<?php
// api/employee/apply_leave.php - Submit leave request
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

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

$employee_id = isset($input['employee_id']) ? (int)$input['employee_id'] : 0;
$user_id = $_SESSION['user_id'];
$leave_type_id = isset($input['leave_type_id']) ? (int)$input['leave_type_id'] : 0;
$from_date = isset($input['from_date']) ? trim($input['from_date']) : '';
$to_date = isset($input['to_date']) ? trim($input['to_date']) : '';
$reason = isset($input['reason']) ? trim($input['reason']) : '';

// Validation
$errors = [];

if ($employee_id <= 0) {
    $errors[] = "Invalid employee ID";
}

if ($leave_type_id <= 0) {
    $errors[] = "Please select a leave type";
}

if (empty($from_date)) {
    $errors[] = "From date is required";
}

if (empty($to_date)) {
    $errors[] = "To date is required";
}

if (empty($reason)) {
    $errors[] = "Please provide a reason for leave";
} elseif (strlen($reason) < 10) {
    $errors[] = "Please provide more details (minimum 10 characters)";
}

// Verify employee belongs to this user
$verify = mysqli_prepare($conn, "SELECT id, first_name, last_name, department_id FROM employees WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($verify, "ii", $employee_id, $user_id);
mysqli_stmt_execute($verify);
$verify_result = mysqli_stmt_get_result($verify);
$employee = mysqli_fetch_assoc($verify_result);

if (!$employee) {
    $errors[] = "Access denied";
}
mysqli_stmt_close($verify);

// Validate dates
if (!empty($from_date) && !empty($to_date)) {
    $from_timestamp = strtotime($from_date);
    $to_timestamp = strtotime($to_date);
    $today = strtotime(date('Y-m-d'));
    
    if ($from_timestamp < $today) {
        $errors[] = "From date cannot be in the past";
    }
    
    if ($from_timestamp > $to_timestamp) {
        $errors[] = "From date must be before or equal to To date";
    }
    
    // Calculate total days
    $date_diff = ($to_timestamp - $from_timestamp) / (60 * 60 * 24) + 1;
    $total_days = round($date_diff, 1);
} else {
    $total_days = 1;
}

// Check if already have pending request for overlapping dates
$overlap_query = "SELECT COUNT(*) as overlap FROM leave_requests 
                  WHERE employee_id = ? 
                  AND status IN ('pending', 'approved')
                  AND ((from_date BETWEEN ? AND ?) OR (to_date BETWEEN ? AND ?) 
                  OR (? BETWEEN from_date AND to_date) OR (? BETWEEN from_date AND to_date))";
$overlap_stmt = mysqli_prepare($conn, $overlap_query);
mysqli_stmt_bind_param($overlap_stmt, "issssss", $employee_id, $from_date, $to_date, $from_date, $to_date, $from_date, $to_date);
mysqli_stmt_execute($overlap_stmt);
$overlap_result = mysqli_stmt_get_result($overlap_stmt);
$overlap_data = mysqli_fetch_assoc($overlap_result);

if ($overlap_data['overlap'] > 0) {
    $errors[] = "You already have a pending or approved leave request for these dates";
}
mysqli_stmt_close($overlap_stmt);

// Check leave balance
$balance_query = "SELECT 
                    lt.days_per_year,
                    COALESCE(lb.total_days, lt.days_per_year) as total_days,
                    COALESCE(lb.used_days, 0) as used_days
                  FROM leave_types lt
                  LEFT JOIN leave_balances lb ON lt.id = lb.leave_type_id 
                      AND lb.employee_id = ? AND lb.year = YEAR(CURDATE())
                  WHERE lt.id = ? AND lt.status = 'active'";
$balance_stmt = mysqli_prepare($conn, $balance_query);
mysqli_stmt_bind_param($balance_stmt, "ii", $employee_id, $leave_type_id);
mysqli_stmt_execute($balance_stmt);
$balance_result = mysqli_stmt_get_result($balance_stmt);
$balance = mysqli_fetch_assoc($balance_result);
mysqli_stmt_close($balance_stmt);

if ($balance) {
    $available_days = $balance['total_days'] - $balance['used_days'];
    
    // Skip balance check for Loss of Pay (leave_type_id = 4)
    if ($leave_type_id != 4 && $total_days > $available_days) {
        $errors[] = "Insufficient leave balance. Available: $available_days days, Requested: $total_days days";
    }
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Insert leave request
$insert_query = "INSERT INTO leave_requests (
                    employee_id, leave_type_id, from_date, to_date, 
                    total_days, reason, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";

$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, "iissds", 
    $employee_id, $leave_type_id, $from_date, $to_date,
    $total_days, $reason
);

$inserted = mysqli_stmt_execute($insert_stmt);
$request_id = mysqli_insert_id($conn);
mysqli_stmt_close($insert_stmt);

if ($inserted) {
    // Log activity
    $log_query = "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address) 
                  VALUES (?, 'leave_requested', ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $description = "Submitted leave request for $total_days days from $from_date to $to_date";
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    mysqli_stmt_bind_param($log_stmt, "iss", $user_id, $description, $ip_address);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    
    // Get leave type name
    $leave_name_query = "SELECT leave_name FROM leave_types WHERE id = ?";
    $name_stmt = mysqli_prepare($conn, $leave_name_query);
    mysqli_stmt_bind_param($name_stmt, "i", $leave_type_id);
    mysqli_stmt_execute($name_stmt);
    $name_result = mysqli_stmt_get_result($name_stmt);
    $leave_type_data = mysqli_fetch_assoc($name_result);
    $leave_name = $leave_type_data['leave_name'] ?? 'Leave';
    mysqli_stmt_close($name_stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Leave request submitted successfully',
        'request' => [
            'id' => $request_id,
            'leave_type' => $leave_name,
            'from_date' => $from_date,
            'to_date' => $to_date,
            'total_days' => $total_days,
            'reason' => $reason,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ],
        'next_steps' => [
            'Your request has been sent for approval',
            'You will be notified once approved/rejected',
            'Check status in Leave Requests section'
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to submit leave request']);
}

mysqli_close($conn);
?>