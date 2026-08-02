<?php
// api/hr/add_department.php - Add new department
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

$department_code = strtoupper(trim($input['code'] ?? ''));
$department_name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$head_of_dept = isset($input['head_of_dept']) ? (int)$input['head_of_dept'] : null;
$parent_department = isset($input['parent_department']) ? (int)$input['parent_department'] : null;

// Validation
$errors = [];

if (empty($department_code)) {
    $errors[] = "Department code is required";
} elseif (strlen($department_code) > 20) {
    $errors[] = "Department code must be less than 20 characters";
}

if (empty($department_name)) {
    $errors[] = "Department name is required";
} elseif (strlen($department_name) > 100) {
    $errors[] = "Department name must be less than 100 characters";
}

// Check for duplicate department code
$check_code = mysqli_prepare($conn, "SELECT id FROM departments WHERE department_code = ?");
mysqli_stmt_bind_param($check_code, "s", $department_code);
mysqli_stmt_execute($check_code);
$code_result = mysqli_stmt_get_result($check_code);
if (mysqli_fetch_assoc($code_result)) {
    $errors[] = "Department code already exists";
}
mysqli_stmt_close($check_code);

// Check for duplicate department name
$check_name = mysqli_prepare($conn, "SELECT id FROM departments WHERE department_name = ?");
mysqli_stmt_bind_param($check_name, "s", $department_name);
mysqli_stmt_execute($check_name);
$name_result = mysqli_stmt_get_result($check_name);
if (mysqli_fetch_assoc($name_result)) {
    $errors[] = "Department name already exists";
}
mysqli_stmt_close($check_name);

// Validate head of department if provided
if ($head_of_dept > 0) {
    $check_head = mysqli_prepare($conn, "SELECT id FROM employees WHERE id = ? AND status = 'active'");
    mysqli_stmt_bind_param($check_head, "i", $head_of_dept);
    mysqli_stmt_execute($check_head);
    $head_result = mysqli_stmt_get_result($check_head);
    if (!mysqli_fetch_assoc($head_result)) {
        $errors[] = "Selected head of department not found";
    }
    mysqli_stmt_close($check_head);
}

// Validate parent department if provided
if ($parent_department > 0) {
    $check_parent = mysqli_prepare($conn, "SELECT id FROM departments WHERE id = ?");
    mysqli_stmt_bind_param($check_parent, "i", $parent_department);
    mysqli_stmt_execute($check_parent);
    $parent_result = mysqli_stmt_get_result($check_parent);
    if (!mysqli_fetch_assoc($parent_result)) {
        $errors[] = "Parent department not found";
    }
    mysqli_stmt_close($check_parent);
}

// Return errors if any
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Insert department
$insert_query = "INSERT INTO departments (department_code, department_name, description, head_of_dept, parent_department, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, 'active', NOW())";

$insert_stmt = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($insert_stmt, "sssii", $department_code, $department_name, $description, $head_of_dept, $parent_department);
$inserted = mysqli_stmt_execute($insert_stmt);
$department_id = mysqli_insert_id($conn);
mysqli_stmt_close($insert_stmt);

if ($inserted) {
    // Log activity
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $log_query = "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address) 
                  VALUES (?, 'department_added', ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $description_log = "Added new department: $department_name ($department_code)";
    mysqli_stmt_bind_param($log_stmt, "iss", $_SESSION['user_id'], $description_log, $ip_address);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    
    echo json_encode([
        'success' => true,
        'message' => 'Department added successfully',
        'department' => [
            'id' => $department_id,
            'code' => $department_code,
            'name' => $department_name,
            'description' => $description,
            'head_of_dept' => $head_of_dept,
            'parent_department' => $parent_department
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add department']);
}

mysqli_close($conn);
?>