<?php
// ============================================================
// CIBIL REPAIR CRM - Get Recent Activity API
// Endpoint: /api/get_recent_activity.php
// Method: GET
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// ============================================================
// CHECK IF TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'activity_logs'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    // Create activity_logs table
    $createTable = "
        CREATE TABLE activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_name VARCHAR(100) NOT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_name),
            INDEX idx_action (action),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    mysqli_query($conn, $createTable);
    
    // Insert sample data
    $sampleData = "
        INSERT INTO activity_logs (user_name, action, details, ip_address) VALUES
        ('Admin', 'Login', 'Admin logged in successfully', '127.0.0.1'),
        ('Admin', 'Created Lead', 'New lead added: John Doe', '127.0.0.1'),
        ('Admin', 'Updated Partner', 'Partner details updated', '127.0.0.1'),
        ('Admin', 'Payment Processed', 'Payment of ₹15,000 processed', '127.0.0.1'),
        ('Admin', 'Generated Report', 'Monthly report generated', '127.0.0.1')
    ";
    mysqli_query($conn, $sampleData);
}

// ============================================================
// GET PARAMETERS
// ============================================================

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$user_name = isset($_GET['user_name']) ? trim($_GET['user_name']) : '';
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';

// ============================================================
// BUILD QUERY
// ============================================================

$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(user_name LIKE ? OR action LIKE ? OR details LIKE ?)";
    $searchWild = "%$search%";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
    $types .= 'sss';
}

if (!empty($user_name) && $user_name !== 'all') {
    $where[] = "user_name = ?";
    $params[] = $user_name;
    $types .= 's';
}

if (!empty($action) && $action !== 'all') {
    $where[] = "action LIKE ?";
    $params[] = "%$action%";
    $types .= 's';
}

if (!empty($from_date)) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $from_date;
    $types .= 's';
}

if (!empty($to_date)) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $to_date;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ============================================================
// GET TOTAL COUNT
// ============================================================

$countQuery = "SELECT COUNT(*) as total FROM activity_logs $whereClause";
$stmt = mysqli_prepare($conn, $countQuery);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$countResult = mysqli_stmt_get_result($stmt);
$totalRow = mysqli_fetch_assoc($countResult);
$total = $totalRow ? intval($totalRow['total']) : 0;
mysqli_stmt_close($stmt);

// ============================================================
// GET ACTIVITIES
// ============================================================

$query = "SELECT id, user_name, action, details, ip_address, created_at 
          FROM activity_logs 
          $whereClause 
          ORDER BY created_at DESC 
          LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$activities = [];
while ($row = mysqli_fetch_assoc($result)) {
    $time = strtotime($row['created_at']);
    $diff = time() - $time;
    
    if ($diff < 60) {
        $timeAgo = $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        $timeAgo = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        $timeAgo = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        $timeAgo = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        $timeAgo = date('M d, Y H:i', $time);
    }
    
    $activities[] = [
        'id' => intval($row['id']),
        'user_name' => $row['user_name'] ?? 'System',
        'action' => $row['action'],
        'details' => $row['details'],
        'ip_address' => $row['ip_address'] ?? '0.0.0.0',
        'created_at' => $row['created_at'],
        'time_ago' => $timeAgo
    ];
}
mysqli_stmt_close($stmt);

// ============================================================
// GET USER LIST
// ============================================================

$users = [];
$userResult = mysqli_query($conn, "SELECT DISTINCT user_name FROM activity_logs ORDER BY user_name");
while ($row = mysqli_fetch_assoc($userResult)) {
    $users[] = $row['user_name'];
}

// ============================================================
// GET ACTION LIST
// ============================================================

$actions = [];
$actionResult = mysqli_query($conn, "SELECT DISTINCT action FROM activity_logs ORDER BY action");
while ($row = mysqli_fetch_assoc($actionResult)) {
    $actions[] = $row['action'];
}

// ============================================================
// GET STATS
// ============================================================

$stats = [];

// Total
$sResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM activity_logs");
$sRow = mysqli_fetch_assoc($sResult);
$stats['total'] = $sRow ? intval($sRow['total']) : 0;

// Today
$sResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = CURDATE()");
$sRow = mysqli_fetch_assoc($sResult);
$stats['today'] = $sRow ? intval($sRow['total']) : 0;

// This week
$sResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM activity_logs WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())");
$sRow = mysqli_fetch_assoc($sResult);
$stats['this_week'] = $sRow ? intval($sRow['total']) : 0;

// This month
$sResult = mysqli_query($conn, "SELECT COUNT(*) as total FROM activity_logs WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$sRow = mysqli_fetch_assoc($sResult);
$stats['this_month'] = $sRow ? intval($sRow['total']) : 0;

// Most active user
$sResult = mysqli_query($conn, "SELECT user_name, COUNT(*) as count FROM activity_logs GROUP BY user_name ORDER BY count DESC LIMIT 1");
$sRow = mysqli_fetch_assoc($sResult);
$stats['most_active_user'] = $sRow ? $sRow['user_name'] : 'N/A';

// Most common action
$sResult = mysqli_query($conn, "SELECT action, COUNT(*) as count FROM activity_logs GROUP BY action ORDER BY count DESC LIMIT 1");
$sRow = mysqli_fetch_assoc($sResult);
$stats['most_common_action'] = $sRow ? $sRow['action'] : 'N/A';

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Recent activity retrieved successfully',
    'data' => [
        'activities' => $activities,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'search' => $search,
        'user_name' => $user_name,
        'action' => $action,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'users' => $users,
        'actions' => $actions,
        'stats' => $stats,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>