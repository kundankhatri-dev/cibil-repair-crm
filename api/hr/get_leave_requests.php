<?php
// api/hr/get_leave_requests.php - Get all leave requests for HR
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
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$leave_type_filter = isset($_GET['leave_type_id']) ? (int)$_GET['leave_type_id'] : 0;
$department_filter = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Build query
$query = "SELECT 
            lr.id,
            lr.employee_id,
            lr.leave_type_id,
            lr.from_date,
            lr.to_date,
            DATE_FORMAT(lr.from_date, '%d %b %Y') as from_date_formatted,
            DATE_FORMAT(lr.to_date, '%d %b %Y') as to_date_formatted,
            lr.total_days,
            lr.reason,
            lr.status,
            lr.approved_by,
            DATE_FORMAT(lr.approved_at, '%d %b %Y %h:%i %p') as approved_at_formatted,
            lr.comments,
            DATE_FORMAT(lr.created_at, '%d %b %Y') as created_at_formatted,
            DATE_FORMAT(lr.created_at, '%h:%i %p') as created_time,
            -- Employee details
            CONCAT(e.first_name, ' ', e.last_name) as employee_name,
            e.employee_code,
            e.work_email as employee_email,
            e.personal_phone as employee_phone,
            -- Department
            d.department_name,
            -- Leave type
            lt.leave_code,
            lt.leave_name,
            -- Approver details
            CONCAT(approver.first_name, ' ', approver.last_name) as approved_by_name
          FROM leave_requests lr
          JOIN employees e ON lr.employee_id = e.id
          JOIN leave_types lt ON lr.leave_type_id = lt.id
          LEFT JOIN departments d ON e.department_id = d.id
          LEFT JOIN employees approver ON lr.approved_by = approver.id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($status_filter)) {
    $query .= " AND lr.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($leave_type_filter > 0) {
    $query .= " AND lr.leave_type_id = ?";
    $params[] = $leave_type_filter;
    $types .= "i";
}

if ($department_filter > 0) {
    $query .= " AND e.department_id = ?";
    $params[] = $department_filter;
    $types .= "i";
}

$query .= " ORDER BY 
            CASE lr.status 
                WHEN 'pending' THEN 1 
                WHEN 'approved' THEN 2 
                WHEN 'rejected' THEN 3 
                ELSE 4 
            END ASC,
            lr.created_at DESC 
            LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leave_requests = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM leave_requests lr WHERE 1=1";
if (!empty($status_filter)) {
    $count_query .= " AND status = '$status_filter'";
}
if ($leave_type_filter > 0) {
    $count_query .= " AND leave_type_id = $leave_type_filter";
}
$count_result = mysqli_query($conn, $count_query);
$total_count = mysqli_fetch_assoc($count_result)['total'] ?? 0;

// Get counts by status
$status_counts_query = "SELECT 
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                            COUNT(*) as total
                        FROM leave_requests
                        WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
$status_counts_result = mysqli_query($conn, $status_counts_query);
$status_counts = mysqli_fetch_assoc($status_counts_result);

// Get leave types for filter
$leave_types_query = "SELECT id, leave_code, leave_name FROM leave_types WHERE status = 'active'";
$leave_types_result = mysqli_query($conn, $leave_types_query);
$leave_types = mysqli_fetch_all($leave_types_result, MYSQLI_ASSOC);

// Get departments for filter
$depts_query = "SELECT id, department_name FROM departments WHERE status = 'active'";
$depts_result = mysqli_query($conn, $depts_query);
$departments = mysqli_fetch_all($depts_result, MYSQLI_ASSOC);

// Format the data
foreach ($leave_requests as &$lr) {
    // Status badge class
    $status_class = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'cancelled' => 'secondary'
    ][$lr['status']] ?? 'secondary';
    $lr['status_badge'] = $status_class;
    $lr['status_label'] = ucfirst($lr['status']);
    
    // Check if leave is upcoming
    $today = date('Y-m-d');
    if ($lr['status'] === 'approved' && $lr['from_date'] >= $today) {
        $days_until = ceil((strtotime($lr['from_date']) - strtotime($today)) / 86400);
        $lr['days_until'] = $days_until;
        $lr['is_upcoming'] = true;
    } else {
        $lr['is_upcoming'] = false;
    }
    
    // Get remaining balance for this leave type
    $balance_query = "SELECT (total_days - used_days) as remaining 
                      FROM leave_balances 
                      WHERE employee_id = ? AND leave_type_id = ? AND year = YEAR(CURDATE())";
    $balance_stmt = mysqli_prepare($conn, $balance_query);
    mysqli_stmt_bind_param($balance_stmt, "ii", $lr['employee_id'], $lr['leave_type_id']);
    mysqli_stmt_execute($balance_stmt);
    $balance_result = mysqli_stmt_get_result($balance_stmt);
    $balance_data = mysqli_fetch_assoc($balance_result);
    $lr['remaining_balance'] = $balance_data['remaining'] ?? 0;
    mysqli_stmt_close($balance_stmt);
}

echo json_encode([
    'success' => true,
    'requests' => $leave_requests,
    'total' => count($leave_requests),
    'total_all' => (int)$total_count,
    'has_more' => ($offset + $limit) < $total_count,
    'pending_count' => (int)($status_counts['pending'] ?? 0),
    'approved_count' => (int)($status_counts['approved'] ?? 0),
    'rejected_count' => (int)($status_counts['rejected'] ?? 0),
    'total_count' => (int)($status_counts['total'] ?? 0),
    'leave_types' => $leave_types,
    'departments' => $departments,
    'filters' => [
        'status' => $status_filter,
        'leave_type_id' => $leave_type_filter,
        'department_id' => $department_filter,
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