<?php
// api/partner/get_commission_details.php
// Partner Get Commission Details API - View commission details and analytics

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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

// ========== DETERMINE LEADS AND COMMISSION TABLES ==========
$leadsTable = 'partner_leads';
$checkLeadsTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkLeadsTable) == 0) {
    $leadsTable = 'leads';
}

$commissionTable = 'partner_commissions';
$checkCommTable = mysqli_query($conn, "SHOW TABLES LIKE '$commissionTable'");
if (mysqli_num_rows($checkCommTable) == 0) {
    $commissionTable = 'partner_commission';
    $checkCommTable2 = mysqli_query($conn, "SHOW TABLES LIKE '$commissionTable'");
    if (mysqli_num_rows($checkCommTable2) == 0) {
        $commissionTable = null;
    }
}

// ========== GET INPUT PARAMETERS ==========
$commission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$period = isset($_GET['period']) ? $_GET['period'] : 'all'; // all, month, quarter, year
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all'; // all, paid, pending
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Validate limit
if ($limit < 1 || $limit > 100) {
    $limit = 20;
}
$offset = ($page - 1) * $limit;

// Validate dates
if (!empty($from_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) {
    $from_date = '';
}
if (!empty($to_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
    $to_date = '';
}

// ========== CASE 1: GET SPECIFIC COMMISSION DETAIL ==========
if ($commission_id > 0) {
    if (!$commissionTable) {
        echo json_encode(['success' => false, 'error' => 'Commission table not found']);
        exit;
    }
    
    // Get column names for commission table
    $commColumns = [];
    $commColResult = mysqli_query($conn, "SHOW COLUMNS FROM $commissionTable");
    if ($commColResult) {
        while ($col = mysqli_fetch_assoc($commColResult)) {
            $commColumns[] = $col['Field'];
        }
    }
    
    $statusCol = in_array('status', $commColumns) ? 'status' : 'payment_status';
    $serviceCol = in_array('service_type', $commColumns) ? 'service_type' : (in_array('service', $commColumns) ? 'service' : 'service');
    $custNameCol = in_array('customer_name', $commColumns) ? 'customer_name' : (in_array('name', $commColumns) ? 'name' : 'customer_name');
    
    $query = "SELECT 
                c.id,
                c.lead_id,
                c.$custNameCol as customer_name,
                c.$serviceCol as service,
                c.service_amount,
                c.commission_rate,
                c.commission_amount,
                c.$statusCol as payment_status,
                DATE_FORMAT(c.created_at, '%d-%m-%Y %h:%i %p') as created_at,
                DATE_FORMAT(c.paid_date, '%d-%m-%Y') as paid_date,
                l.customer_phone,
                l.customer_email,
                l.source,
                l.status as lead_status
              FROM $commissionTable c
              LEFT JOIN $leadsTable l ON c.lead_id = l.id
              WHERE c.id = ? AND c.partner_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
        exit;
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $commission_id, $partner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $commission = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$commission) {
        echo json_encode(['success' => false, 'error' => 'Commission record not found']);
        exit;
    }
    
    // Format values
    $commission['service_amount'] = (float)($commission['service_amount'] ?? 0);
    $commission['commission_amount'] = (float)($commission['commission_amount'] ?? 0);
    $commission['commission_rate'] = (float)($commission['commission_rate'] ?? 10);
    
    // Add status badge
    $status_badges = [
        'paid' => 'success',
        'pending' => 'warning',
        'cancelled' => 'danger'
    ];
    $commission['status_badge'] = $status_badges[$commission['payment_status']] ?? 'secondary';
    
    echo json_encode([
        'success' => true,
        'commission' => $commission
    ]);
    exit;
}

// ========== CASE 2: GET SUMMARY AND LIST ==========
if (!$commissionTable) {
    // If no commission table, calculate from leads table
    $date_condition = "";
    if (!empty($from_date) && !empty($to_date)) {
        $date_condition = "AND created_at BETWEEN '$from_date' AND '$to_date'";
    } elseif ($period == 'month') {
        $date_condition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    } elseif ($period == 'quarter') {
        $date_condition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
    } elseif ($period == 'year') {
        $date_condition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    }
    
    // Summary from leads table
    $summary_query = "SELECT 
                        COUNT(*) as total_transactions,
                        SUM(commission_amount) as total_commission,
                        SUM(commission_amount) as pending_commission,
                        0 as paid_commission,
                        10 as avg_rate
                      FROM $leadsTable 
                      WHERE partner_id = ? AND status = 'converted' AND commission_amount > 0 $date_condition";
    
    $stmt = mysqli_prepare($conn, $summary_query);
    mysqli_stmt_bind_param($stmt, "i", $partner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $summary = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // List from leads table
    $list_query = "SELECT 
                    id as commission_id,
                    id as lead_id,
                    customer_name,
                    service_type as service,
                    commission_amount,
                    created_at,
                    'earned' as payment_status
                  FROM $leadsTable 
                  WHERE partner_id = ? AND status = 'converted' AND commission_amount > 0 $date_condition
                  ORDER BY created_at DESC
                  LIMIT ? OFFSET ?";
    
    $stmt = mysqli_prepare($conn, $list_query);
    mysqli_stmt_bind_param($stmt, "iii", $partner_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $commissions = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM $leadsTable WHERE partner_id = ? AND status = 'converted' AND commission_amount > 0 $date_condition";
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, "i", $partner_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_count = mysqli_fetch_assoc($count_result)['total'] ?? 0;
    mysqli_stmt_close($count_stmt);
    
    echo json_encode([
        'success' => true,
        'summary' => [
            'total_transactions' => (int)($summary['total_transactions'] ?? 0),
            'total_commission' => round((float)($summary['total_commission'] ?? 0), 2),
            'paid_commission' => round((float)($summary['paid_commission'] ?? 0), 2),
            'pending_commission' => round((float)($summary['pending_commission'] ?? 0), 2),
            'avg_rate' => (float)($summary['avg_rate'] ?? 10)
        ],
        'commissions' => $commissions,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => (int)$total_count,
            'total_pages' => ceil($total_count / $limit)
        ],
        'filters' => [
            'period' => $period,
            'status' => $status_filter,
            'from_date' => $from_date,
            'to_date' => $to_date
        ]
    ]);
    exit;
}

// ========== CASE 3: GET FROM COMMISSION TABLE (with full features) ==========

// Build date condition
$date_condition = "";
if (!empty($from_date) && !empty($to_date)) {
    $date_condition = "AND c.created_at BETWEEN '$from_date' AND '$to_date 23:59:59'";
} elseif ($period == 'month') {
    $date_condition = "AND c.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($period == 'quarter') {
    $date_condition = "AND c.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
} elseif ($period == 'year') {
    $date_condition = "AND c.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}

// Build status condition
$status_condition = "";
if ($status_filter !== 'all') {
    $status_condition = "AND c.status = '$status_filter'";
}

// Get column names
$commColumns = [];
$commColResult = mysqli_query($conn, "SHOW COLUMNS FROM $commissionTable");
if ($commColResult) {
    while ($col = mysqli_fetch_assoc($commColResult)) {
        $commColumns[] = $col['Field'];
    }
}

$statusCol = in_array('status', $commColumns) ? 'status' : 'payment_status';
$serviceCol = in_array('service_type', $commColumns) ? 'service_type' : (in_array('service', $commColumns) ? 'service' : 'service');
$custNameCol = in_array('customer_name', $commColumns) ? 'customer_name' : (in_array('name', $commColumns) ? 'name' : 'customer_name');

// ========== GET SUMMARY STATISTICS ==========
$summary_query = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(commission_amount) as total_commission,
                    SUM(CASE WHEN $statusCol = 'paid' THEN commission_amount ELSE 0 END) as paid_commission,
                    SUM(CASE WHEN $statusCol = 'pending' THEN commission_amount ELSE 0 END) as pending_commission,
                    AVG(commission_rate) as avg_rate
                  FROM $commissionTable 
                  WHERE partner_id = ? $date_condition $status_condition";

$stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$summary = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// ========== GET MONTHLY BREAKDOWN ==========
$monthly_query = "SELECT 
                    DATE_FORMAT(created_at, '%b %Y') as month,
                    DATE_FORMAT(created_at, '%Y-%m') as month_key,
                    COUNT(*) as count,
                    SUM(commission_amount) as amount
                  FROM $commissionTable 
                  WHERE partner_id = ? 
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY created_at DESC
                  LIMIT 12";

$stmt3 = mysqli_prepare($conn, $monthly_query);
mysqli_stmt_bind_param($stmt3, "i", $partner_id);
mysqli_stmt_execute($stmt3);
$result3 = mysqli_stmt_get_result($stmt3);
$monthly_breakdown = mysqli_fetch_all($result3, MYSQLI_ASSOC);
mysqli_stmt_close($stmt3);

// ========== GET COMMISSION LIST WITH PAGINATION ==========
$list_query = "SELECT 
                c.id,
                c.lead_id,
                c.$custNameCol as customer_name,
                c.$serviceCol as service,
                c.service_amount,
                c.commission_rate,
                c.commission_amount,
                c.$statusCol as payment_status,
                DATE_FORMAT(c.created_at, '%d-%m-%Y') as created_date,
                DATE_FORMAT(c.paid_date, '%d-%m-%Y') as paid_date
              FROM $commissionTable c
              WHERE c.partner_id = ? $date_condition $status_condition
              ORDER BY c.created_at DESC
              LIMIT ? OFFSET ?";

$stmt2 = mysqli_prepare($conn, $list_query);
mysqli_stmt_bind_param($stmt2, "iii", $partner_id, $limit, $offset);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);
$commissions = mysqli_fetch_all($result2, MYSQLI_ASSOC);
mysqli_stmt_close($stmt2);

// Format commission amounts
foreach ($commissions as &$comm) {
    $comm['commission_amount'] = (float)$comm['commission_amount'];
    $comm['service_amount'] = (float)($comm['service_amount'] ?? 0);
    $comm['commission_rate'] = (float)($comm['commission_rate'] ?? 10);
    
    // Add status badge
    $status_badges = [
        'paid' => 'success',
        'pending' => 'warning',
        'cancelled' => 'danger'
    ];
    $comm['status_badge'] = $status_badges[$comm['payment_status']] ?? 'secondary';
}

// ========== GET TOTAL COUNT FOR PAGINATION ==========
$count_query = "SELECT COUNT(*) as total FROM $commissionTable WHERE partner_id = ? $date_condition $status_condition";
$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $partner_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_count = mysqli_fetch_assoc($count_result)['total'] ?? 0;
mysqli_stmt_close($count_stmt);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'summary' => [
        'total_transactions' => (int)($summary['total_transactions'] ?? 0),
        'total_commission' => round((float)($summary['total_commission'] ?? 0), 2),
        'paid_commission' => round((float)($summary['paid_commission'] ?? 0), 2),
        'pending_commission' => round((float)($summary['pending_commission'] ?? 0), 2),
        'avg_rate' => round((float)($summary['avg_rate'] ?? 10), 2)
    ],
    'monthly_breakdown' => $monthly_breakdown,
    'commissions' => $commissions,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => (int)$total_count,
        'total_pages' => ceil($total_count / $limit),
        'has_next' => ($offset + $limit) < $total_count,
        'has_previous' => $page > 1
    ],
    'filters' => [
        'period' => $period,
        'status' => $status_filter,
        'from_date' => $from_date,
        'to_date' => $to_date,
        'page' => $page,
        'limit' => $limit
    ]
]);

mysqli_close($conn);
?>