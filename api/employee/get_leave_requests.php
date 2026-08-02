<?php
// api/employee/get_leave_requests.php - Get employee leave request history
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
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

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

// Build query
$query = "SELECT 
            lr.id,
            lr.leave_type_id,
            lt.leave_code,
            lt.leave_name,
            lr.from_date,
            lr.to_date,
            DATE_FORMAT(lr.from_date, '%d %b %Y') as from_date_formatted,
            DATE_FORMAT(lr.to_date, '%d %b %Y') as to_date_formatted,
            lr.total_days,
            lr.reason,
            lr.status,
            lr.approved_by,
            DATE_FORMAT(lr.approved_at, '%d %b %Y') as approved_at_formatted,
            lr.comments,
            DATE_FORMAT(lr.created_at, '%d %b %Y') as created_at_formatted,
            DATEDIFF(lr.from_date, CURDATE()) as days_until
          FROM leave_requests lr
          JOIN leave_types lt ON lr.leave_type_id = lt.id
          WHERE lr.employee_id = ?";

$params = [$employee_id];
$types = "i";

if ($status_filter !== 'all') {
    $query .= " AND lr.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " ORDER BY lr.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leave_requests = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM leave_requests WHERE employee_id = ?";
if ($status_filter !== 'all') {
    $count_query .= " AND status = ?";
}
$count_stmt = mysqli_prepare($conn, $count_query);
if ($status_filter !== 'all') {
    mysqli_stmt_bind_param($count_stmt, "is", $employee_id, $status_filter);
} else {
    mysqli_stmt_bind_param($count_stmt, "i", $employee_id);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_data = mysqli_fetch_assoc($count_result);
$total_count = $count_data['total'] ?? 0;
mysqli_stmt_close($count_stmt);

// Get status counts for filtering
$status_counts_query = "SELECT 
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                            COUNT(*) as total
                        FROM leave_requests 
                        WHERE employee_id = ?";
$status_stmt = mysqli_prepare($conn, $status_counts_query);
mysqli_stmt_bind_param($status_stmt, "i", $employee_id);
mysqli_stmt_execute($status_stmt);
$status_result = mysqli_stmt_get_result($status_stmt);
$status_counts = mysqli_fetch_assoc($status_result);
mysqli_stmt_close($status_stmt);

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
    
    // Check if upcoming leave (within next 7 days)
    if ($lr['status'] === 'approved' && $lr['days_until'] <= 7 && $lr['days_until'] >= 0) {
        $lr['is_upcoming'] = true;
    } else {
        $lr['is_upcoming'] = false;
    }
}

echo json_encode([
    'success' => true,
    'data' => $leave_requests,
    'total' => count($leave_requests),
    'total_all' => (int)$total_count,
    'has_more' => ($offset + $limit) < $total_count,
    'status_counts' => [
        'pending' => (int)($status_counts['pending'] ?? 0),
        'approved' => (int)($status_counts['approved'] ?? 0),
        'rejected' => (int)($status_counts['rejected'] ?? 0),
        'cancelled' => (int)($status_counts['cancelled'] ?? 0),
        'total' => (int)($status_counts['total'] ?? 0)
    ],
    'filters' => [
        'status' => $status_filter,
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