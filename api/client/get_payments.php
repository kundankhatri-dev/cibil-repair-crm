<?php
// api/client/get_payments.php - Get all payments for client
session_start();
header('Content-Type: application/json');

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

// Get client_id (supports both client and partner/admin viewing)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';
$viewer_id = $_SESSION['user_id'] ?? null;

// If partner or admin viewing, allow client_id from GET
if (in_array($viewer_role, ['admin', 'partner']) && isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
    
    // Verify partner has access to this client
    if ($viewer_role === 'partner' && $viewer_id) {
        $check = mysqli_prepare($conn, "SELECT COUNT(*) FROM leads WHERE partner_id = ? AND customer_id = ?");
        mysqli_stmt_bind_param($check, "ii", $viewer_id, $client_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_bind_result($check, $count);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);
        
        if ($count == 0) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
    }
}

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// ========== CREATE PAYMENTS TABLE IF NOT EXISTS ==========
$create_table = "CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    transaction_id VARCHAR(100) UNIQUE,
    invoice_id INT,
    case_id INT,
    case_no VARCHAR(50),
    service_name VARCHAR(200),
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('UPI', 'Credit Card', 'Debit Card', 'Net Banking', 'NEFT/RTGS', 'Cash') DEFAULT 'UPI',
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    reference_number VARCHAR(100),
    payment_date DATETIME,
    verified_at DATETIME,
    verified_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date),
    INDEX idx_transaction (transaction_id)
)";

mysqli_query($conn, $create_table);

// Generate unique transaction ID function
function generateTransactionId($conn, $client_id) {
    $prefix = 'TXN';
    $year = date('Y');
    $month = date('m');
    
    $query = "SELECT transaction_id FROM payments WHERE transaction_id LIKE '$prefix$year$month%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_num = (int)substr($row['transaction_id'], -6);
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    
    return $prefix . $year . $month . str_pad($new_num, 6, '0', STR_PAD_LEFT);
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Validate limit
if ($limit < 1 || $limit > 200) {
    $limit = 50;
}

// ========== BUILD QUERY ==========
$query = "SELECT 
            p.*,
            CASE 
                WHEN p.status = 'pending' THEN 0
                WHEN p.status = 'processing' THEN 30
                WHEN p.status = 'completed' THEN 100
                WHEN p.status = 'failed' THEN 100
                WHEN p.status = 'refunded' THEN 100
                ELSE 0
            END as progress
          FROM payments p
          WHERE p.client_id = ?";

$params = [$client_id];
$types = "i";

// Add status filter
if ($status_filter !== 'all') {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add date range filter
if (!empty($date_from)) {
    $query .= " AND DATE(p.payment_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to)) {
    $query .= " AND DATE(p.payment_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY p.payment_date DESC, p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payments = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== GET PAYMENT SUMMARY ==========
$summary_query = "SELECT 
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_paid,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
                    SUM(CASE WHEN status = 'processing' THEN amount ELSE 0 END) as total_processing,
                    SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as total_failed,
                    SUM(CASE WHEN status = 'refunded' THEN amount ELSE 0 END) as total_refunded,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as paid_count,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                    COUNT(*) as total_count
                  FROM payments WHERE client_id = ?";

$summary_stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($summary_stmt, "i", $client_id);
mysqli_stmt_execute($summary_stmt);
$summary_result = mysqli_stmt_get_result($summary_stmt);
$summary = mysqli_fetch_assoc($summary_result);
mysqli_stmt_close($summary_stmt);

// ========== GET TOTAL COUNT FOR PAGINATION ==========
$total_query = "SELECT COUNT(*) as total FROM payments WHERE client_id = ?";
if ($status_filter !== 'all') {
    $total_query .= " AND status = ?";
}
if (!empty($date_from)) {
    $total_query .= " AND DATE(payment_date) >= ?";
}
if (!empty($date_to)) {
    $total_query .= " AND DATE(payment_date) <= ?";
}

$total_stmt = mysqli_prepare($conn, $total_query);
$total_params = [$client_id];
$total_types = "i";

if ($status_filter !== 'all') {
    $total_params[] = $status_filter;
    $total_types .= "s";
}
if (!empty($date_from)) {
    $total_params[] = $date_from;
    $total_types .= "s";
}
if (!empty($date_to)) {
    $total_params[] = $date_to;
    $total_types .= "s";
}

mysqli_stmt_bind_param($total_stmt, $total_types, ...$total_params);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_data = mysqli_fetch_assoc($total_result);
$total_count = $total_data['total'] ?? 0;
mysqli_stmt_close($total_stmt);

// ========== GET LAST 6 MONTHS PAYMENT TREND ==========
$trend_query = "SELECT 
                    DATE_FORMAT(payment_date, '%Y-%m') as month,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as paid_amount,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as paid_count
                FROM payments 
                WHERE client_id = ? 
                    AND payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    AND status = 'completed'
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month ASC";

$trend_stmt = mysqli_prepare($conn, $trend_query);
mysqli_stmt_bind_param($trend_stmt, "i", $client_id);
mysqli_stmt_execute($trend_stmt);
$trend_result = mysqli_stmt_get_result($trend_stmt);
$payment_trend = mysqli_fetch_all($trend_result, MYSQLI_ASSOC);
mysqli_stmt_close($trend_stmt);

// ========== FORMAT PAYMENTS ==========
$status_labels = [
    'pending' => 'Pending',
    'processing' => 'Processing',
    'completed' => 'Completed',
    'failed' => 'Failed',
    'refunded' => 'Refunded'
];

$status_colors = [
    'pending' => 'warning',
    'processing' => 'info',
    'completed' => 'success',
    'failed' => 'danger',
    'refunded' => 'secondary'
];

$method_icons = [
    'UPI' => 'fa-qrcode',
    'Credit Card' => 'fa-credit-card',
    'Debit Card' => 'fa-credit-card',
    'Net Banking' => 'fa-university',
    'NEFT/RTGS' => 'fa-exchange-alt',
    'Cash' => 'fa-money-bill'
];

foreach ($payments as &$p) {
    $p['amount'] = (float)$p['amount'];
    $p['amount_formatted'] = '₹' . number_format($p['amount'], 2);
    $p['status_label'] = $status_labels[$p['status']] ?? ucfirst($p['status']);
    $p['status_badge'] = $status_colors[$p['status']] ?? 'secondary';
    $p['method_icon'] = $method_icons[$p['payment_method']] ?? 'fa-credit-card';
    
    if ($p['payment_date']) {
        $p['payment_date_formatted'] = date('d M Y', strtotime($p['payment_date']));
        $p['payment_time'] = date('h:i A', strtotime($p['payment_date']));
    } else {
        $p['payment_date_formatted'] = date('d M Y', strtotime($p['created_at']));
    }
    
    if ($p['verified_at']) {
        $p['verified_date_formatted'] = date('d M Y', strtotime($p['verified_at']));
    }
    
    // Generate invoice link if completed
    if ($p['status'] === 'completed') {
        $p['invoice_url'] = "api/client/download_invoice.php?payment_id=" . $p['id'];
    }
}

// Format summary
$summary['total_paid'] = (float)($summary['total_paid'] ?? 0);
$summary['total_pending'] = (float)($summary['total_pending'] ?? 0);
$summary['total_processing'] = (float)($summary['total_processing'] ?? 0);
$summary['total_failed'] = (float)($summary['total_failed'] ?? 0);
$summary['total_refunded'] = (float)($summary['total_refunded'] ?? 0);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'data' => $payments,
    'total' => count($payments),
    'total_all' => (int)$total_count,
    'has_more' => ($offset + $limit) < $total_count,
    'summary' => [
        'total_paid' => $summary['total_paid'],
        'total_paid_formatted' => '₹' . number_format($summary['total_paid'], 2),
        'total_pending' => $summary['total_pending'],
        'total_pending_formatted' => '₹' . number_format($summary['total_pending'], 2),
        'total_processing' => $summary['total_processing'],
        'total_processing_formatted' => '₹' . number_format($summary['total_processing'], 2),
        'total_failed' => $summary['total_failed'],
        'total_refunded' => $summary['total_refunded'],
        'paid_count' => (int)($summary['paid_count'] ?? 0),
        'pending_count' => (int)($summary['pending_count'] ?? 0),
        'total_count' => (int)($summary['total_count'] ?? 0)
    ],
    'payment_trend' => $payment_trend,
    'filters' => [
        'status' => $status_filter,
        'date_from' => $date_from,
        'date_to' => $date_to,
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