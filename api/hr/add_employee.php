<?php
// api/hr/add_employee.php - Add new employee
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

// Extract fields
$first_name = trim($input['first_name'] ?? '');
$last_name = trim($input['last_name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$department_id = isset($input['department_id']) ? (int)$input['department_id'] : 0;
$designation_id = isset($input['designation_id']) ? (int)$input['designation_id'] : 0;
$joining_date = trim($input['joining_date'] ?? '');
$basic_salary = isset($input['basic_salary']) ? (float)$input['basic_salary'] : 0;
$gender = trim($input['gender'] ?? '');
$date_of_birth = trim($input['date_of_birth'] ?? '');
$employment_type = trim($input['employment_type'] ?? 'permanent');
$address = trim($input['address'] ?? '');
$city = trim($input['city'] ?? '');
$state = trim($input['state'] ?? '');
$pincode = trim($input['pincode'] ?? '');
$pan_number = strtoupper(trim($input['pan_number'] ?? ''));
$bank_name = trim($input['bank_name'] ?? '');
$bank_account_no = trim($input['bank_account_no'] ?? '');
$ifsc_code = strtoupper(trim($input['ifsc_code'] ?? ''));

// Validation
$errors = [];

if (empty($first_name)) {
    $errors[] = "First name is required";
}

if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
    $errors[] = "Phone number must be 10 digits";
}

if ($department_id <= 0) {
    $errors[] = "Department is required";
}

if ($designation_id <= 0) {
    $errors[] = "Designation is required";
}

if (empty($joining_date)) {
    $errors[] = "Joining date is required";
}

// Check if email already exists
$check_email = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($check_email, "s", $email);
mysqli_stmt_execute($check_email);
$email_result = mysqli_stmt_get_result($check_email);
if (mysqli_fetch_assoc($email_result)) {
    $errors[] = "Email already registered";
}
mysqli_stmt_close($check_email);

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Generate employee code
$year = date('Y');
$emp_count_query = "SELECT COUNT(*) as count FROM employees WHERE YEAR(created_at) = $year";
$count_result = mysqli_query($conn, $emp_count_query);
$count = mysqli_fetch_assoc($count_result)['count'] ?? 0;
$employee_code = 'EMP' . $year . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

// Default password (they can change after first login)
$default_password = 'password123';
$password_hash = password_hash($default_password, PASSWORD_DEFAULT);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Create user account
    $user_query = "INSERT INTO users (name, email, phone, password, role, status, created_at) 
                   VALUES (?, ?, ?, ?, 'employee', 'active', NOW())";
    $user_stmt = mysqli_prepare($conn, $user_query);
    $full_name = $first_name . ' ' . $last_name;
    mysqli_stmt_bind_param($user_stmt, "ssss", $full_name, $email, $phone, $password_hash);
    mysqli_stmt_execute($user_stmt);
    $user_id = mysqli_insert_id($conn);
    mysqli_stmt_close($user_stmt);
    
    if (!$user_id) {
        throw new Exception("Failed to create user account");
    }
    
    // 2. Calculate CTC if basic salary provided
    $hra = $basic_salary * 0.5;
    $special_allowance = $basic_salary * 0.2;
    $total_ctc = $basic_salary + $hra + $special_allowance;
    
    // 3. Add employee record
    $emp_query = "INSERT INTO employees (
                    user_id, employee_code, first_name, last_name, gender,
                    date_of_birth, personal_email, work_email, personal_phone,
                    department_id, designation_id, joining_date, employment_type,
                    basic_salary, hra, special_allowance, total_ctc,
                    current_address, city, state, pincode, pan_number,
                    bank_name, bank_account_no, ifsc_code, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    
    $emp_stmt = mysqli_prepare($conn, $emp_query);
    mysqli_stmt_bind_param($emp_stmt, 
        "issssssssiissddddssssssss",
        $user_id, $employee_code, $first_name, $last_name, $gender,
        $date_of_birth, $email, $email, $phone,
        $department_id, $designation_id, $joining_date, $employment_type,
        $basic_salary, $hra, $special_allowance, $total_ctc,
        $address, $city, $state, $pincode, $pan_number,
        $bank_name, $bank_account_no, $ifsc_code
    );
    mysqli_stmt_execute($emp_stmt);
    $employee_id = mysqli_insert_id($conn);
    mysqli_stmt_close($emp_stmt);
    
    if (!$employee_id) {
        throw new Exception("Failed to create employee record");
    }
    
    // 4. Add leave balances for the year
    $current_year = date('Y');
    $leave_types = [1, 2, 3]; // CL, SL, EL
    foreach ($leave_types as $lt) {
        $days = ($lt == 1) ? 12 : (($lt == 2) ? 12 : 15);
        $balance_query = "INSERT INTO leave_balances (employee_id, leave_type_id, year, total_days, used_days, pending_days) 
                          VALUES (?, ?, ?, ?, 0, 0)";
        $balance_stmt = mysqli_prepare($conn, $balance_query);
        mysqli_stmt_bind_param($balance_stmt, "iiii", $employee_id, $lt, $current_year, $days);
        mysqli_stmt_execute($balance_stmt);
        mysqli_stmt_close($balance_stmt);
    }
    
    // 5. Log activity
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $log_query = "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address) 
                  VALUES (?, 'employee_added', ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $description = "Added new employee: $full_name ($employee_code)";
    mysqli_stmt_bind_param($log_stmt, "iss", $_SESSION['user_id'], $description, $ip_address);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Employee added successfully',
        'employee' => [
            'id' => $employee_id,
            'code' => $employee_code,
            'name' => $full_name,
            'email' => $email,
            'default_password' => $default_password,
            'joining_date' => $joining_date
        ],
        'note' => 'Default password is: ' . $default_password . ' (Employee should change this on first login)'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn);
?>