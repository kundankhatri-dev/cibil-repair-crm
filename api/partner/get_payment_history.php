<?php
// api/partner/get_payment_history.php
// Partner Get Payment History API - View payout history and summary

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role, name FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$partner_name = $role_data['name'];

// ========== ENSURE PAYOUTS TABLE EXISTS ==========
$payoutsTable = 'partner_payouts';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$payoutsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS $payoutsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        request_date DATETIME NOT NULL,
        status ENUM('pending', 'approved', 'paid', 'rejected') DEFAULT 'pending',
        paid_date DATETIME DEFAULT NULL,
        transaction_id VARCHAR(100) DEFAULT NULL,
        payment_method VARCHAR(50) DEFAULT NULL,
        remarks TEXT,
        approved_by INT DEFAULT NULL,
        approved_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        INDEX idx_status (status),
        INDEX idx_request_date (request_date),
        FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createTable);
}

// ========== GET INPUT PARAMETERS ==========
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc'; // asc or desc

// Validate limit
if ($limit < 1 || $limit > 200) {
    $limit = 50;
}

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) {
    $from_date = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
    $to_date = date('Y-m-d');
}
if (strtotime($from_date) > strtotime($to_date)) {
    $temp = $from_date;
    $from_date = $to_date;
    $to_date = $temp;
}

// Validate status
$valid_statuses = ['all', 'pending', 'approved', 'paid', 'rejected'];
if (!in_array($status, $valid_statuses)) {
    $status = 'all';
}

// Validate sort
if (!in_array($sort, ['asc', 'desc'])) {
    $sort = 'desc';
}

// Convert dates to datetime range
$from_datetime = $from_date . ' 00:00:00';
$to_datetime = $to_date . ' 23:59:59';

// ========== BUILD QUERY ==========
$query = "SELECT 
            p.id,
            p.amount,
            p.status,
            DATE_FORMAT(p.request_date, '%d-%m-%Y') as request_date,
            DATE_FORMAT(p.request_date, '%Y-%m-%d %H:%i:%s') as request_datetime,
            DATE_FORMAT(p.paid_date, '%d-%m-%Y') as paid_date,
            DATE_FORMAT(p.paid_date, '%Y-%m-%d %H:%i:%s') as paid_datetime,
            p.transaction_id,
            p.payment_method,
            p.remarks,
            CASE 
                WHEN p.status = 'paid' THEN 'success'
                WHEN p.status = 'approved' THEN 'info'
                WHEN p.status = 'pending' THEN 'warning'
                WHEN p.status = 'rejected' THEN 'danger'
                ELSE 'secondary'
            END as status_badge,
            u.name as approved_by_name
          FROM $payoutsTable p
          LEFT JOIN users u ON p.approved_by = u.id
          WHERE p.partner_id = ?";

$params = [$partner_id];
$types = "i";

if ($status !== 'all') {
    $query .= " AND p.status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " AND p.request_date BETWEEN ? AND ?";
$params[] = $from_datetime;
$params[] = $to_datetime;
$types .= "ss";

$query .= " ORDER BY p.request_date " . ($sort === 'desc' ? 'DESC' : 'ASC') . " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payments = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== GET TOTAL COUNT ==========
$count_query = "SELECT COUNT(*) as total FROM $payoutsTable WHERE partner_id = ?";
$count_params = [$partner_id];
$count_types = "i";

if ($status !== 'all') {
    $count_query .= " AND status = ?";
    $count_params[] = $status;
    $count_types .= "s";
}

$count_query .= " AND request_date BETWEEN ? AND ?";
$count_params[] = $from_datetime;
$count_params[] = $to_datetime;
$count_types .= "ss";

$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total = mysqli_fetch_assoc($count_result)['total'] ?? 0;
mysqli_stmt_close($count_stmt);

// ========== GET SUMMARY ==========
$summary_query = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
                    SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_approved,
                    SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END) as total_rejected,
                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as count_paid,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as count_pending,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as count_approved,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as count_rejected,
                    MAX(CASE WHEN status = 'paid' THEN paid_date ELSE NULL END) as last_payment_date,
                    AVG(CASE WHEN status = 'paid' THEN amount ELSE NULL END) as avg_payment_amount
                  FROM $payoutsTable 
                  WHERE partner_id = ? AND request_date BETWEEN ? AND ?";
                  
$summary_stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($summary_stmt, "iss", $partner_id, $from_datetime, $to_datetime);
mysqli_stmt_execute($summary_stmt);
$summary_result = mysqli_stmt_get_result($summary_stmt);
$summary = mysqli_fetch_assoc($summary_result);
mysqli_stmt_close($summary_stmt);

// ========== GET MONTHLY BREAKDOWN ==========
$monthly_query = "SELECT 
                    DATE_FORMAT(request_date, '%b %Y') as month,
                    DATE_FORMAT(request_date, '%Y-%m') as month_key,
                    COUNT(*) as requests,
                    SUM(amount) as amount,
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount
                  FROM $payoutsTable 
                  WHERE partner_id = ? 
                  GROUP BY DATE_FORMAT(request_date, '%Y-%m')
                  ORDER BY request_date DESC
                  LIMIT 12";
                  
$monthly_stmt = mysqli_prepare($conn, $monthly_query);
mysqli_stmt_bind_param($monthly_stmt, "i", $partner_id);
mysqli_stmt_execute($monthly_stmt);
$monthly_result = mysqli_stmt_get_result($monthly_stmt);
$monthly_breakdown = mysqli_fetch_all($monthly_result, MYSQLI_ASSOC);
mysqli_stmt_close($monthly_stmt);

// ========== FORMAT VALUES ==========
foreach ($payments as &$payment) {
    $payment['amount'] = (float)$payment['amount'];
    $payment['amount_formatted'] = '₹' . number_format($payment['amount'], 2);
}

$summary['total_amount'] = (float)($summary['total_amount'] ?? 0);
$summary['total_paid'] = (float)($summary['total_paid'] ?? 0);
$summary['total_pending'] = (float)($summary['total_pending'] ?? 0);
$summary['total_approved'] = (float)($summary['total_approved'] ?? 0);
$summary['total_rejected'] = (float)($summary['total_rejected'] ?? 0);
$summary['total_requests'] = (int)($summary['total_requests'] ?? 0);
$summary['count_paid'] = (int)($summary['count_paid'] ?? 0);
$summary['count_pending'] = (int)($summary['count_pending'] ?? 0);
$summary['count_approved'] = (int)($summary['count_approved'] ?? 0);
$summary['count_rejected'] = (int)($summary['count_rejected'] ?? 0);
$summary['avg_payment_amount'] = round((float)($summary['avg_payment_amount'] ?? 0), 2);
$summary['total_amount_formatted'] = '₹' . number_format($summary['total_amount'], 2);
$summary['total_paid_formatted'] = '₹' . number_format($summary['total_paid'], 2);
$summary['total_pending_formatted'] = '₹' . number_format($summary['total_pending'], 2);

// ========== CALCULATE SUCCESS RATE ==========
$success_rate = 0;
if ($summary['total_requests'] > 0 && $summary['count_paid'] > 0) {
    $success_rate = round(($summary['count_paid'] / $summary['total_requests']) * 100, 2);
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'partner' => [
        'id' => $partner_id,
        'name' => $partner_name
    ],
    'payments' => $payments,
    'summary' => $summary,
    'monthly_breakdown' => $monthly_breakdown,
    'success_rate' => $success_rate,
    'period' => [
        'from_date' => $from_date,
        'to_date' => $to_date,
        'days' => ceil((strtotime($to_date) - strtotime($from_date)) / (60 * 60 * 24)) + 1
    ],
    'filters' => [
        'status' => $status,
        'sort' => $sort,
        'limit' => $limit,
        'offset' => $offset
    ],
    'pagination' => [
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset,
        'current_page' => floor($offset / $limit) + 1,
        'total_pages' => ceil($total / $limit),
        'has_next' => ($offset + $limit) < $total,
        'has_previous' => $offset > 0
    ]
]);

mysqli_close($conn);
?>