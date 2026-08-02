<?php
// api/client/get_invoices.php - Get all GST invoices for client
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

// Get client_id
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';
$viewer_id = $_SESSION['user_id'] ?? null;

// Partner/admin access check
if (in_array($viewer_role, ['admin', 'partner']) && isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
    
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

// ========== CREATE GST INVOICES TABLE ==========
$create_table = "CREATE TABLE IF NOT EXISTS gst_invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    invoice_no VARCHAR(50) UNIQUE,
    payment_id INT,
    case_id INT,
    case_no VARCHAR(50),
    service_name VARCHAR(200),
    
    -- Amount breakdown
    subtotal DECIMAL(12,2) NOT NULL,
    cgst_rate DECIMAL(5,2) DEFAULT 9.00,
    cgst_amount DECIMAL(12,2) DEFAULT 0,
    sgst_rate DECIMAL(5,2) DEFAULT 9.00,
    sgst_amount DECIMAL(12,2) DEFAULT 0,
    igst_rate DECIMAL(5,2) DEFAULT 0,
    igst_amount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL,
    
    -- GST classification (intra-state = CGST+SGST, inter-state = IGST)
    gst_type ENUM('intra_state', 'inter_state') DEFAULT 'intra_state',
    
    -- Status
    status ENUM('draft', 'issued', 'paid', 'overdue', 'cancelled') DEFAULT 'issued',
    
    -- Dates
    issue_date DATE NOT NULL,
    due_date DATE,
    paid_date DATE,
    
    -- Billing details (GST compliant)
    billing_name VARCHAR(200),
    billing_address TEXT,
    billing_gstin VARCHAR(50),
    billing_pan VARCHAR(50),
    billing_state VARCHAR(100),
    billing_state_code VARCHAR(10),
    billing_email VARCHAR(100),
    billing_phone VARCHAR(20),
    
    -- Company details (seller)
    company_name VARCHAR(200) DEFAULT 'CIBIL Repair Services',
    company_gstin VARCHAR(50) DEFAULT '29AAKCI1234G1Z',
    company_pan VARCHAR(50) DEFAULT 'AAKCI1234G',
    company_address TEXT,
    company_state VARCHAR(100) DEFAULT 'Karnataka',
    company_state_code VARCHAR(10) DEFAULT '29',
    
    -- Payment terms
    payment_terms TEXT,
    notes TEXT,
    pdf_path VARCHAR(500),
    
    -- HSN/SAC code (for services)
    sac_code VARCHAR(20) DEFAULT '998311',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_client (client_id),
    INDEX idx_invoice_no (invoice_no),
    INDEX idx_status (status),
    INDEX idx_issue_date (issue_date)
)";

mysqli_query($conn, $create_table);

// ========== GENERATE INVOICE NUMBER FUNCTION ==========
function generateInvoiceNo($conn, $client_id) {
    $prefix = 'INV';
    $year = date('Y');
    $month = date('m');
    
    $query = "SELECT invoice_no FROM gst_invoices WHERE invoice_no LIKE '$prefix$year$month%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_num = (int)substr($row['invoice_no'], -6);
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

if ($limit < 1 || $limit > 200) {
    $limit = 50;
}

// ========== BUILD QUERY ==========
$query = "SELECT 
            i.*,
            DATE_FORMAT(i.issue_date, '%d %b %Y') as issue_date_formatted,
            DATE_FORMAT(i.due_date, '%d %b %Y') as due_date_formatted,
            DATE_FORMAT(i.paid_date, '%d %b %Y') as paid_date_formatted,
            CASE 
                WHEN i.status = 'issued' AND i.due_date < CURDATE() THEN 'overdue'
                ELSE i.status
            END as computed_status
          FROM gst_invoices i
          WHERE i.client_id = ?";

$params = [$client_id];
$types = "i";

if ($status_filter !== 'all') {
    $query .= " AND i.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $query .= " AND i.issue_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to)) {
    $query .= " AND i.issue_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY i.issue_date DESC, i.id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoices = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== GET SUMMARY ==========
$summary_query = "SELECT 
                    COUNT(*) as total_invoices,
                    SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as total_paid,
                    SUM(CASE WHEN status = 'issued' AND due_date >= CURDATE() THEN total_amount ELSE 0 END) as total_due,
                    SUM(CASE WHEN status = 'issued' AND due_date < CURDATE() THEN total_amount ELSE 0 END) as total_overdue,
                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                    COUNT(CASE WHEN status = 'issued' THEN 1 END) as due_count,
                    COUNT(CASE WHEN status = 'issued' AND due_date < CURDATE() THEN 1 END) as overdue_count
                  FROM gst_invoices WHERE client_id = ?";

$summary_stmt = mysqli_prepare($conn, $summary_query);
mysqli_stmt_bind_param($summary_stmt, "i", $client_id);
mysqli_stmt_execute($summary_stmt);
$summary_result = mysqli_stmt_get_result($summary_stmt);
$summary = mysqli_fetch_assoc($summary_result);
mysqli_stmt_close($summary_stmt);

// ========== GET TOTAL COUNT ==========
$total_query = "SELECT COUNT(*) as total FROM gst_invoices WHERE client_id = ?";
if ($status_filter !== 'all') {
    $total_query .= " AND status = ?";
}
if (!empty($date_from)) {
    $total_query .= " AND issue_date >= ?";
}
if (!empty($date_to)) {
    $total_query .= " AND issue_date <= ?";
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

// ========== FORMAT INVOICES ==========
$status_labels = [
    'draft' => 'Draft',
    'issued' => 'Issued',
    'paid' => 'Paid ✓',
    'overdue' => 'Overdue ⚠️',
    'cancelled' => 'Cancelled'
];

$status_colors = [
    'draft' => 'secondary',
    'issued' => 'info',
    'paid' => 'success',
    'overdue' => 'danger',
    'cancelled' => 'secondary'
];

$gst_type_labels = [
    'intra_state' => 'CGST + SGST (Intra-State)',
    'inter_state' => 'IGST (Inter-State)'
];

foreach ($invoices as &$inv) {
    $inv['total_amount'] = (float)$inv['total_amount'];
    $inv['subtotal'] = (float)$inv['subtotal'];
    $inv['cgst_amount'] = (float)$inv['cgst_amount'];
    $inv['sgst_amount'] = (float)$inv['sgst_amount'];
    $inv['igst_amount'] = (float)$inv['igst_amount'];
    
    $inv['total_amount_formatted'] = '₹' . number_format($inv['total_amount'], 2);
    $inv['subtotal_formatted'] = '₹' . number_format($inv['subtotal'], 2);
    $inv['status_label'] = $status_labels[$inv['computed_status']] ?? $status_labels[$inv['status']];
    $inv['status_badge'] = $status_colors[$inv['computed_status']] ?? $status_colors[$inv['status']];
    $inv['gst_type_label'] = $gst_type_labels[$inv['gst_type']] ?? '';
    
    // GST breakdown string
    if ($inv['gst_type'] === 'intra_state') {
        $inv['gst_breakdown'] = "CGST @ {$inv['cgst_rate']}%: ₹" . number_format($inv['cgst_amount'], 2) . 
                                " | SGST @ {$inv['sgst_rate']}%: ₹" . number_format($inv['sgst_amount'], 2);
    } else {
        $inv['gst_breakdown'] = "IGST @ {$inv['igst_rate']}%: ₹" . number_format($inv['igst_amount'], 2);
    }
    
    // Download and view URLs
    $inv['download_url'] = "api/client/download_gst_invoice.php?id={$inv['id']}";
    $inv['view_url'] = "api/client/view_gst_invoice.php?id={$inv['id']}";
    
    // Check if payment is overdue
    if ($inv['computed_status'] === 'overdue') {
        $days_overdue = ceil((time() - strtotime($inv['due_date'])) / 86400);
        $inv['days_overdue'] = $days_overdue;
        $inv['overdue_message'] = "Payment is overdue by $days_overdue day(s). Late fee may apply.";
    }
}

// Format summary
$summary['total_paid'] = (float)($summary['total_paid'] ?? 0);
$summary['total_due'] = (float)($summary['total_due'] ?? 0);
$summary['total_overdue'] = (float)($summary['total_overdue'] ?? 0);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'data' => $invoices,
    'total' => count($invoices),
    'total_all' => (int)$total_count,
    'has_more' => ($offset + $limit) < $total_count,
    'summary' => [
        'total_invoices' => (int)($summary['total_invoices'] ?? 0),
        'total_paid' => $summary['total_paid'],
        'total_paid_formatted' => '₹' . number_format($summary['total_paid'], 2),
        'total_due' => $summary['total_due'],
        'total_due_formatted' => '₹' . number_format($summary['total_due'], 2),
        'total_overdue' => $summary['total_overdue'],
        'total_overdue_formatted' => '₹' . number_format($summary['total_overdue'], 2),
        'paid_count' => (int)($summary['paid_count'] ?? 0),
        'due_count' => (int)($summary['due_count'] ?? 0),
        'overdue_count' => (int)($summary['overdue_count'] ?? 0)
    ],
    'gst_settings' => [
        'company_name' => 'CIBIL Repair Services',
        'company_gstin' => '29AAKCI1234G1Z',
        'company_pan' => 'AAKCI1234G',
        'sac_code' => '998311',
        'cgst_rate' => 9.00,
        'sgst_rate' => 9.00,
        'igst_rate' => 18.00
    ],
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