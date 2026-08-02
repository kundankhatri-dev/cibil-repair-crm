<?php
// api/hr/get_departments.php - Get all departments
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

// Get all departments
$query = "SELECT 
            d.id,
            d.department_code,
            d.department_name,
            d.description,
            d.head_of_dept,
            CONCAT(e.first_name, ' ', e.last_name) as head_name,
            d.parent_department,
            pd.department_name as parent_name,
            d.status,
            d.created_at,
            DATE_FORMAT(d.created_at, '%d %b %Y') as created_at_formatted,
            (SELECT COUNT(*) FROM employees WHERE department_id = d.id AND status = 'active') as employee_count
          FROM departments d
          LEFT JOIN employees e ON d.head_of_dept = e.id
          LEFT JOIN departments pd ON d.parent_department = pd.id
          ORDER BY d.department_name";

$result = mysqli_query($conn, $query);
$departments = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get available employees for head selection
$employees_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM employees WHERE status = 'active' ORDER BY first_name";
$emp_result = mysqli_query($conn, $employees_query);
$available_heads = mysqli_fetch_all($emp_result, MYSQLI_ASSOC);

// Get parent departments list
$parent_depts = array_filter($departments, function($dept) {
    return true; // Include all departments as potential parents
});

echo json_encode([
    'success' => true,
    'departments' => $departments,
    'available_heads' => $available_heads,
    'parent_departments' => $parent_depts,
    'total' => count($departments)
]);

mysqli_close($conn);
?>