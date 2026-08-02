<?php
// api/hr/get_employees.php - Get all employees with details
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
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Build query
$query = "SELECT 
            e.id,
            e.employee_code,
            e.first_name,
            e.last_name,
            CONCAT(e.first_name, ' ', e.last_name) as full_name,
            e.gender,
            e.date_of_birth,
            DATE_FORMAT(e.date_of_birth, '%d %b %Y') as date_of_birth_formatted,
            e.marital_status,
            e.nationality,
            e.personal_email,
            e.work_email,
            e.personal_phone,
            e.work_phone,
            e.current_address,
            e.city,
            e.state,
            e.pincode,
            e.department_id,
            d.department_name,
            e.designation_id,
            ds.designation_name,
            e.reporting_to,
            CONCAT(reporting.first_name, ' ', reporting.last_name) as reporting_to_name,
            e.employment_type,
            e.joining_date,
            DATE_FORMAT(e.joining_date, '%d %b %Y') as joining_date_formatted,
            e.confirmation_date,
            e.probation_period,
            e.basic_salary,
            e.hra,
            e.special_allowance,
            e.other_allowance,
            e.total_ctc,
            e.bank_name,
            e.bank_account_no,
            e.ifsc_code,
            e.uan_number,
            e.esi_number,
            e.status,
            e.created_at,
            DATE_FORMAT(e.created_at, '%d %b %Y') as created_at_formatted,
            u.email as user_email
          FROM employees e
          LEFT JOIN departments d ON e.department_id = d.id
          LEFT JOIN designations ds ON e.designation_id = ds.id
          LEFT JOIN employees reporting ON e.reporting_to = reporting.id
          LEFT JOIN users u ON e.user_id = u.id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR e.work_email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

if ($department_id > 0) {
    $query .= " AND e.department_id = ?";
    $params[] = $department_id;
    $types .= "i";
}

if (!empty($status)) {
    $query .= " AND e.status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " ORDER BY e.first_name LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$employees = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM employees WHERE 1=1";
if (!empty($search)) {
    $count_query .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR employee_code LIKE '%$search%')";
}
if ($department_id > 0) {
    $count_query .= " AND department_id = $department_id";
}
if (!empty($status)) {
    $count_query .= " AND status = '$status'";
}
$count_result = mysqli_query($conn, $count_query);
$total_count = mysqli_fetch_assoc($count_result)['total'] ?? 0;

// Get all departments for filter
$dept_query = "SELECT id, department_code, department_name FROM departments WHERE status = 'active' ORDER BY department_name";
$dept_result = mysqli_query($conn, $dept_query);
$departments = mysqli_fetch_all($dept_result, MYSQLI_ASSOC);

// Get all designations
$desig_query = "SELECT id, designation_code, designation_name, department_id FROM designations WHERE status = 'active' ORDER BY designation_name";
$desig_result = mysqli_query($conn, $desig_query);
$designations = mysqli_fetch_all($desig_result, MYSQLI_ASSOC);

// Format employees data
foreach ($employees as &$emp) {
    $emp['basic_salary_formatted'] = '₹' . number_format($emp['basic_salary'] ?? 0, 2);
    $emp['total_ctc_formatted'] = '₹' . number_format($emp['total_ctc'] ?? 0, 2);
    $emp['account_no_masked'] = $emp['bank_account_no'] ? 'XXXX' . substr($emp['bank_account_no'], -4) : '';
    
    // Calculate age
    if ($emp['date_of_birth']) {
        $dob = new DateTime($emp['date_of_birth']);
        $now = new DateTime();
        $emp['age'] = $now->diff($dob)->y;
    } else {
        $emp['age'] = null;
    }
    
    // Status badge class
    $status_class = [
        'active' => 'success',
        'inactive' => 'secondary',
        'terminated' => 'danger',
        'resigned' => 'warning',
        'on_leave' => 'info'
    ][$emp['status']] ?? 'secondary';
    $emp['status_badge'] = $status_class;
}

echo json_encode([
    'success' => true,
    'employees' => $employees,
    'total' => count($employees),
    'total_all' => (int)$total_count,
    'has_more' => ($offset + $limit) < $total_count,
    'departments' => $departments,
    'designations' => $designations,
    'filters' => [
        'search' => $search,
        'department_id' => $department_id,
        'status' => $status,
        'limit' => $limit,
        'offset' => $offset
    ],
    'pagination' => [
        'current_page' => floor($offset / $limit) + 1,
        'per_page' => $limit,
        'total_pages' => ceil($total_count / $limit),
        'total_records' => (int)$total_count
    ]
]);

mysqli_close($conn);
?>