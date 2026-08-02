<?php
// api/employee/mark_attendance.php - Mark employee attendance
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
$check_in_time = isset($input['check_in_time']) ? trim($input['check_in_time']) : null;
$check_out_time = isset($input['check_out_time']) ? trim($input['check_out_time']) : null;
$status = isset($input['status']) ? trim($input['status']) : 'present';
$notes = isset($input['notes']) ? trim($input['notes']) : '';

// Validate
if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid employee ID']);
    exit;
}

// Verify employee belongs to this user
$verify = mysqli_prepare($conn, "SELECT id, employee_code, first_name, last_name, department_id 
                                  FROM employees WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($verify, "ii", $employee_id, $user_id);
mysqli_stmt_execute($verify);
$verify_result = mysqli_stmt_get_result($verify);
$employee = mysqli_fetch_assoc($verify_result);

if (!$employee) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}
mysqli_stmt_close($verify);

// Validate status
$valid_statuses = ['present', 'absent', 'late', 'half_day'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid attendance status']);
    exit;
}

$today = date('Y-m-d');
$current_time = date('H:i:s');

// If no check-in time provided, use current time
if (empty($check_in_time) && $status !== 'absent') {
    $check_in_time = $current_time;
}

// Calculate late minutes (if check-in after 9:30 AM)
$late_minutes = 0;
if ($check_in_time && $check_in_time > '09:30:00') {
    $late_minutes = (strtotime($check_in_time) - strtotime('09:30:00')) / 60;
    if ($status !== 'late') {
        $status = 'late';
    }
}

// Calculate working hours
$working_hours = null;
if ($check_in_time && $check_out_time) {
    $working_hours = round((strtotime($check_out_time) - strtotime($check_in_time)) / 3600, 1);
}

// Check if attendance already marked for today
$check_query = "SELECT id, check_in_time, check_out_time FROM attendance 
                WHERE employee_id = ? AND attendance_date = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "is", $employee_id, $today);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$existing = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

if ($existing) {
    // Update existing attendance (for check-out)
    if ($check_out_time && !$existing['check_out_time']) {
        $update_query = "UPDATE attendance 
                        SET check_out_time = ?, working_hours = ?, updated_at = NOW()
                        WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sdi", $check_out_time, $working_hours, $existing['id']);
        mysqli_stmt_execute($update_stmt);
        $updated = mysqli_stmt_affected_rows($update_stmt) > 0;
        mysqli_stmt_close($update_stmt);
        
        if ($updated) {
            echo json_encode([
                'success' => true,
                'message' => 'Check-out time recorded successfully',
                'action' => 'checkout'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update check-out time']);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Attendance already marked for today',
            'existing' => $existing
        ]);
    }
    exit;
}

// Insert new attendance record
$insert_query = "INSERT INTO attendance (
                    employee_id, attendance_date, check_in_time, check_out_time, 
                    working_hours, status, late_minutes, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, "isssssis", 
    $employee_id, $today, $check_in_time, $check_out_time,
    $working_hours, $status, $late_minutes, $notes
);

$inserted = mysqli_stmt_execute($insert_stmt);
$attendance_id = mysqli_insert_id($conn);
mysqli_stmt_close($insert_stmt);

if ($inserted) {
    // Log activity
    $log_query = "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address) 
                  VALUES (?, 'attendance_marked', ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $description = "Marked attendance as '" . $status . "' at " . $check_in_time;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    mysqli_stmt_bind_param($log_stmt, "iss", $user_id, $description, $ip_address);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Attendance marked successfully',
        'action' => 'checkin',
        'attendance' => [
            'id' => $attendance_id,
            'date' => $today,
            'check_in_time' => $check_in_time,
            'status' => $status,
            'late_minutes' => $late_minutes
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to mark attendance']);
}

mysqli_close($conn);
?>