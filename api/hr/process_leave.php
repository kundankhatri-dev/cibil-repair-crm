<?php
// api/hr/process_leave.php - Approve or reject leave request
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

$leave_id = isset($input['id']) ? (int)$input['id'] : 0;
$status = isset($input['status']) ? trim($input['status']) : '';
$comments = isset($input['comments']) ? trim($input['comments']) : '';

// Validation
$errors = [];

if ($leave_id <= 0) {
    $errors[] = "Invalid leave request ID";
}

if (!in_array($status, ['approved', 'rejected'])) {
    $errors[] = "Invalid status. Must be 'approved' or 'rejected'";
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Get HR/Admin employee ID
$hr_employee_id = 0;
$hr_query = "SELECT id FROM employees WHERE user_id = ?";
$hr_stmt = mysqli_prepare($conn, $hr_query);
mysqli_stmt_bind_param($hr_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($hr_stmt);
$hr_result = mysqli_stmt_get_result($hr_stmt);
$hr_data = mysqli_fetch_assoc($hr_result);
$hr_employee_id = $hr_data['id'] ?? 0;
mysqli_stmt_close($hr_stmt);

// Get leave request details
$leave_query = "SELECT lr.*, e.first_name, e.last_name, e.employee_code, lt.leave_name, lt.leave_code
                FROM leave_requests lr
                JOIN employees e ON lr.employee_id = e.id
                JOIN leave_types lt ON lr.leave_type_id = lt.id
                WHERE lr.id = ?";
$leave_stmt = mysqli_prepare($conn, $leave_query);
mysqli_stmt_bind_param($leave_stmt, "i", $leave_id);
mysqli_stmt_execute($leave_stmt);
$leave_result = mysqli_stmt_get_result($leave_stmt);
$leave = mysqli_fetch_assoc($leave_result);
mysqli_stmt_close($leave_stmt);

if (!$leave) {
    echo json_encode(['success' => false, 'error' => 'Leave request not found']);
    exit;
}

if ($leave['status'] !== 'pending') {
    echo json_encode(['success' => false, 'error' => 'This leave request has already been processed']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Update leave request status
    $update_query = "UPDATE leave_requests 
                     SET status = ?, approved_by = ?, approved_at = NOW(), comments = ?
                     WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "sisi", $status, $hr_employee_id, $comments, $leave_id);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    // If approved, update leave balance
    if ($status === 'approved') {
        $current_year = date('Y');
        
        // Check if balance exists
        $balance_check = mysqli_prepare($conn, "SELECT id, used_days, total_days FROM leave_balances 
                                                WHERE employee_id = ? AND leave_type_id = ? AND year = ?");
        mysqli_stmt_bind_param($balance_check, "iii", $leave['employee_id'], $leave['leave_type_id'], $current_year);
        mysqli_stmt_execute($balance_check);
        $balance_result = mysqli_stmt_get_result($balance_check);
        $balance = mysqli_fetch_assoc($balance_result);
        mysqli_stmt_close($balance_check);
        
        if ($balance) {
            // Update existing balance
            $new_used_days = $balance['used_days'] + $leave['total_days'];
            $update_balance = mysqli_prepare($conn, "UPDATE leave_balances 
                                                     SET used_days = ?, updated_at = NOW() 
                                                     WHERE id = ?");
            mysqli_stmt_bind_param($update_balance, "di", $new_used_days, $balance['id']);
            mysqli_stmt_execute($update_balance);
            mysqli_stmt_close($update_balance);
        } else {
            // Create new balance record
            $insert_balance = mysqli_prepare($conn, "INSERT INTO leave_balances (employee_id, leave_type_id, year, total_days, used_days) 
                                                     VALUES (?, ?, ?, 0, ?)");
            mysqli_stmt_bind_param($insert_balance, "iiid", $leave['employee_id'], $leave['leave_type_id'], $current_year, $leave['total_days']);
            mysqli_stmt_execute($insert_balance);
            mysqli_stmt_close($insert_balance);
        }
    }
    
    // Log activity
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $employee_name = $leave['first_name'] . ' ' . $leave['last_name'];
    $log_query = "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address) 
                  VALUES (?, 'leave_processed', ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $description = ucfirst($status) . " leave request for $employee_name ({$leave['total_days']} days {$leave['leave_name']})";
    mysqli_stmt_bind_param($log_stmt, "iss", $_SESSION['user_id'], $description, $ip_address);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    
    // Create notification for employee
    $notification_title = $status === 'approved' ? "Leave Approved" : "Leave Rejected";
    $notification_message = $status === 'approved' 
        ? "Your {$leave['leave_name']} request for {$leave['total_days']} days has been approved."
        : "Your {$leave['leave_name']} request has been rejected." . ($comments ? " Reason: $comments" : "");
    
    $notif_query = "INSERT INTO client_notifications (client_id, notification_type, title, message, created_at) 
                    SELECT user_id, 'leave', ?, ?, NOW() FROM employees WHERE id = ?";
    $notif_stmt = mysqli_prepare($conn, $notif_query);
    mysqli_stmt_bind_param($notif_stmt, "ssi", $notification_title, $notification_message, $leave['employee_id']);
    mysqli_stmt_execute($notif_stmt);
    mysqli_stmt_close($notif_stmt);
    
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => "Leave request " . ($status === 'approved' ? "approved" : "rejected") . " successfully",
        'status' => $status,
        'employee' => [
            'id' => $leave['employee_id'],
            'name' => $employee_name,
            'code' => $leave['employee_code']
        ],
        'leave' => [
            'type' => $leave['leave_name'],
            'days' => $leave['total_days']
        ]
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

mysqli_close($conn);
?>