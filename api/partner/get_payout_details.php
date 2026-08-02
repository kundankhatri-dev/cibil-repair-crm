<?php
// api/partner/get_payout_summary.php
// Partner Get Payout Summary API - View payout summary and available balance

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
$role_check = mysqli_prepare($conn, "SELECT role, name, email, phone FROM users WHERE id = ?");
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

// ========== ENSURE TABLES EXIST ==========
// Payouts table
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
        FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createTable);
}

// Leads table
$leadsTable = 'partner_leads';
$checkLeadsTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkLeadsTable) == 0) {
    $leadsTable = 'leads';
}

// ========== GET INPUT PARAMETERS ==========
$payout_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$period = isset($_GET['period']) ? $_GET['period'] : 'all'; // all, month, quarter, year
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Validate period
$valid_periods = ['all', 'month', 'quarter', 'year'];
if (!in_array($period, $valid_periods)) {
    $period = 'all';
}

// Build date condition for summaries
$date_condition = "";
if (!empty($from_date) && !empty($to_date)) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
        $date_condition = " AND request_date BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'";
    }
} elseif ($period == 'month') {
    $date_condition = " AND request_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($period == 'quarter') {
    $date_condition = " AND request_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
} elseif ($period == 'year') {
    $date_condition = " AND request_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}

// ========== CASE 1: GET SPECIFIC PAYOUT DETAIL ==========
if ($payout_id > 0) {
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
                u.bank_name,
                u.account_number,
                u.ifsc_code,
                u.account_holder,
                CASE 
                    WHEN p.status = 'paid' THEN 'success'
                    WHEN p.status = 'approved' THEN 'info'
                    WHEN p.status = 'pending' THEN 'warning'
                    WHEN p.status = 'rejected' THEN 'danger'
                    ELSE 'secondary'
                END as status_badge
              FROM $payoutsTable p
              LEFT JOIN partners u ON p.partner_id = u.user_id
              WHERE p.id = ? AND p.partner_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $payout_id, $partner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $payout = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$payout) {
        echo json_encode(['success' => false, 'error' => 'Payout record not found']);
        exit;
    }
    
    // Format values
    $payout['amount'] = (float)$payout['amount'];
    $payout['amount_formatted'] = '₹' . number_format($payout['amount'], 2);
    
    // Mask account number
    if (!empty($payout['account_number'])) {
        $length = strlen($payout['account_number']);
        $visible = 4;
        $masked = str_repeat('*', max(0, $length - $visible)) . substr($payout['account_number'], -$visible);
        $payout['account_number_masked'] = $masked;
    }
    
    echo json_encode([
        'success' => true,
        'payout' => $payout
    ]);
    exit;
}

// ========== CASE 2: GET PAYOUT SUMMARY ==========

// Get payout summary from payouts table
$summary_query = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_requests,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END) as rejected_amount,
                    MAX(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as last_payout_amount,
                    MAX(CASE WHEN status = 'paid' THEN paid_date ELSE NULL END) as last_payout_date,
                    MIN(CASE WHEN status = 'paid' THEN paid_date ELSE NULL END) as first_payout_date
                  FROM $payoutsTable 
                  WHERE partner_id = ? $date_condition";

$stmt = mysqli_prepare($conn, $summary_query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$summary = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// ========== GET AVAILABLE BALANCE ==========
$available_balance = 0;

// Try from partners table first
$checkPartnersTable = mysqli_query($conn, "SHOW TABLES LIKE 'partners'");
if (mysqli_num_rows($checkPartnersTable) > 0) {
    $balance_query = "SELECT pending_payout, total_commission FROM partners WHERE user_id = ?";
    $balance_stmt = mysqli_prepare($conn, $balance_query);
    if ($balance_stmt) {
        mysqli_stmt_bind_param($balance_stmt, "i", $partner_id);
        mysqli_stmt_execute($balance_stmt);
        $balance_result = mysqli_stmt_get_result($balance_stmt);
        $balance = mysqli_fetch_assoc($balance_result);
        $available_balance = (float)($balance['pending_payout'] ?? 0);
        mysqli_stmt_close($balance_stmt);
    }
}

// If no balance found, calculate from leads
if ($available_balance == 0) {
    $calc_balance_query = "SELECT SUM(commission_amount) as total_commission 
                           FROM $leadsTable 
                           WHERE partner_id = ? AND status = 'converted' AND paid_status != 'paid'";
    $calc_stmt = mysqli_prepare($conn, $calc_balance_query);
    if ($calc_stmt) {
        mysqli_stmt_bind_param($calc_stmt, "i", $partner_id);
        mysqli_stmt_execute($calc_stmt);
        $calc_result = mysqli_stmt_get_result($calc_stmt);
        $calc_balance = mysqli_fetch_assoc($calc_result);
        $total_commission = (float)($calc_balance['total_commission'] ?? 0);
        $paid_amount = (float)($summary['paid_amount'] ?? 0);
        $available_balance = max(0, $total_commission - $paid_amount);
        mysqli_stmt_close($calc_stmt);
    }
}

// ========== MINIMUM PAYOUT AMOUNT ==========
$min_payout = 500;
$max_payout = 100000;

// ========== CALCULATE AVERAGE PAYOUT TIME ==========
$avg_time_query = "SELECT AVG(TIMESTAMPDIFF(DAY, request_date, paid_date)) as avg_days 
                   FROM $payoutsTable 
                   WHERE partner_id = ? AND status = 'paid' AND paid_date IS NOT NULL";
$avg_time_stmt = mysqli_prepare($conn, $avg_time_query);
$avg_processing_days = 0;
if ($avg_time_stmt) {
    mysqli_stmt_bind_param($avg_time_stmt, "i", $partner_id);
    mysqli_stmt_execute($avg_time_stmt);
    $avg_time_result = mysqli_stmt_get_result($avg_time_stmt);
    $avg_time_data = mysqli_fetch_assoc($avg_time_result);
    $avg_processing_days = round($avg_time_data['avg_days'] ?? 0, 1);
    mysqli_stmt_close($avg_time_stmt);
}

// ========== GET MONTHLY PAYOUT TREND ==========
$trend_query = "SELECT 
                    DATE_FORMAT(request_date, '%b %Y') as month,
                    DATE_FORMAT(request_date, '%Y-%m') as month_key,
                    SUM(amount) as requested,
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid
                  FROM $payoutsTable 
                  WHERE partner_id = ? 
                  GROUP BY DATE_FORMAT(request_date, '%Y-%m')
                  ORDER BY request_date DESC
                  LIMIT 6";
$trend_stmt = mysqli_prepare($conn, $trend_query);
$monthly_trend = [];
if ($trend_stmt) {
    mysqli_stmt_bind_param($trend_stmt, "i", $partner_id);
    mysqli_stmt_execute($trend_stmt);
    $trend_result = mysqli_stmt_get_result($trend_stmt);
    $monthly_trend = mysqli_fetch_all($trend_result, MYSQLI_ASSOC);
    mysqli_stmt_close($trend_stmt);
}

// ========== FORMAT SUMMARY VALUES ==========
$formatted_summary = [
    'total_requests' => (int)($summary['total_requests'] ?? 0),
    'total_amount' => round((float)($summary['total_amount'] ?? 0), 2),
    'total_amount_formatted' => '₹' . number_format(($summary['total_amount'] ?? 0), 2),
    'pending_requests' => (int)($summary['pending_requests'] ?? 0),
    'approved_requests' => (int)($summary['approved_requests'] ?? 0),
    'paid_requests' => (int)($summary['paid_requests'] ?? 0),
    'rejected_requests' => (int)($summary['rejected_requests'] ?? 0),
    'pending_amount' => round((float)($summary['pending_amount'] ?? 0), 2),
    'pending_amount_formatted' => '₹' . number_format(($summary['pending_amount'] ?? 0), 2),
    'approved_amount' => round((float)($summary['approved_amount'] ?? 0), 2),
    'paid_amount' => round((float)($summary['paid_amount'] ?? 0), 2),
    'paid_amount_formatted' => '₹' . number_format(($summary['paid_amount'] ?? 0), 2),
    'rejected_amount' => round((float)($summary['rejected_amount'] ?? 0), 2),
    'available_balance' => round($available_balance, 2),
    'available_balance_formatted' => '₹' . number_format($available_balance, 2),
    'min_payout' => $min_payout,
    'max_payout' => $max_payout,
    'can_request_payout' => $available_balance >= $min_payout,
    'max_requestable' => min($available_balance, $max_payout),
    'last_payout_amount' => round((float)($summary['last_payout_amount'] ?? 0), 2),
    'last_payout_amount_formatted' => '₹' . number_format(($summary['last_payout_amount'] ?? 0), 2),
    'last_payout_date' => $summary['last_payout_date'] ?? null,
    'first_payout_date' => $summary['first_payout_date'] ?? null,
    'avg_processing_days' => $avg_processing_days,
    'success_rate' => ($summary['total_requests'] ?? 0) > 0 
        ? round((($summary['paid_requests'] ?? 0) / ($summary['total_requests'] ?? 0)) * 100, 2) 
        : 0
];

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'partner' => [
        'id' => $partner_id,
        'name' => $partner_name
    ],
    'summary' => $formatted_summary,
    'monthly_trend' => $monthly_trend,
    'period' => [
        'type' => $period,
        'from_date' => $from_date ?: null,
        'to_date' => $to_date ?: null
    ]
]);

mysqli_close($conn);
?>