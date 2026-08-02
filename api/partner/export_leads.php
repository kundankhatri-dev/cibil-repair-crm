<?php
// api/partner/export_leads.php
// Partner Export Leads API - Export leads to CSV file

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Check database connection
if (!$conn) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role, name FROM users WHERE id = ?");
if (!$role_check) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// ========== DETERMINE LEADS TABLE ==========
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// ========== GET FILTER PARAMETERS ==========
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : date('Y-m-d');
$export_type = isset($_GET['export_type']) ? trim($_GET['export_type']) : 'leads'; // leads, customers
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10000;

// Validate date range
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

// Validate limit
if ($limit < 1 || $limit > 50000) {
    $limit = 10000;
}

// Validate status filter
$valid_statuses = ['new', 'contacted', 'converted', 'lost', 'all'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

// ========== GET TABLE COLUMN NAMES ==========
$columns = [];
$colResult = mysqli_query($conn, "SHOW COLUMNS FROM $leadsTable");
if ($colResult) {
    while ($col = mysqli_fetch_assoc($colResult)) {
        $columns[] = $col['Field'];
    }
}

// Determine column names
$nameCol = in_array('customer_name', $columns) ? 'customer_name' : (in_array('name', $columns) ? 'name' : 'customer_name');
$phoneCol = in_array('customer_phone', $columns) ? 'customer_phone' : (in_array('phone', $columns) ? 'phone' : 'customer_phone');
$emailCol = in_array('customer_email', $columns) ? 'customer_email' : (in_array('email', $columns) ? 'email' : 'customer_email');
$serviceCol = in_array('service_type', $columns) ? 'service_type' : (in_array('service', $columns) ? 'service' : 'service_type');
$sourceCol = in_array('source', $columns) ? 'source' : 'source';
$notesCol = in_array('notes', $columns) ? 'notes' : null;
$commissionCol = in_array('commission_amount', $columns) ? 'commission_amount' : 'commission_amount';

// ========== BUILD QUERY ==========
if ($export_type === 'customers') {
    // Export only converted leads (customers)
    $query = "SELECT 
                id, 
                $nameCol as customer_name, 
                $phoneCol as customer_phone, 
                COALESCE($emailCol, '-') as customer_email,
                COALESCE($serviceCol, '-') as service,
                DATE_FORMAT(created_at, '%d-%m-%Y') as created_date,
                COALESCE($commissionCol, 0) as commission_amount,
                COALESCE($sourceCol, '-') as source
              FROM $leadsTable 
              WHERE partner_id = ? AND status = 'converted'";
} else {
    // Export all leads
    $query = "SELECT 
                id, 
                $nameCol as customer_name, 
                $phoneCol as customer_phone, 
                COALESCE($emailCol, '-') as customer_email,
                COALESCE($serviceCol, '-') as service,
                status,
                DATE_FORMAT(created_at, '%d-%m-%Y') as created_date,
                COALESCE($commissionCol, 0) as commission_amount,
                COALESCE($sourceCol, '-') as source" .
                ($notesCol ? ", COALESCE(SUBSTRING($notesCol, 1, 500), '-') as notes" : "") . "
              FROM $leadsTable 
              WHERE partner_id = ?";
}

$params = [$partner_id];
$types = "i";

if ($status_filter !== 'all' && $export_type !== 'customers') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " AND DATE(created_at) BETWEEN ? AND ?";
$params[] = $from_date;
$params[] = $to_date;
$types .= "ss";

$query .= " ORDER BY id DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

// ========== EXECUTE QUERY ==========
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leads = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== CHECK IF DATA EXISTS ==========
if (empty($leads)) {
    // Return empty CSV with headers only
    $filename = "leads_export_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if ($export_type === 'customers') {
        fputcsv($output, [
            'Customer ID',
            'Customer Name',
            'Phone Number',
            'Email',
            'Service',
            'Converted Date',
            'Commission Amount (₹)',
            'Source'
        ]);
    } else {
        fputcsv($output, [
            'Lead ID',
            'Customer Name',
            'Phone Number',
            'Email',
            'Service',
            'Status',
            'Created Date',
            'Commission Amount (₹)',
            'Source',
            'Notes'
        ]);
    }
    
    fputcsv($output, ['No data found for the selected criteria']);
    fclose($output);
    exit;
}

// ========== GENERATE CSV ==========
$filename = ($export_type === 'customers' ? 'customers_export_' : 'leads_export_') . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');
header('Pragma: public');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers based on export type
if ($export_type === 'customers') {
    fputcsv($output, [
        'Customer ID',
        'Customer Name',
        'Phone Number',
        'Email',
        'Service',
        'Converted Date',
        'Commission Amount (₹)',
        'Source'
    ]);
} else {
    fputcsv($output, [
        'Lead ID',
        'Customer Name',
        'Phone Number',
        'Email',
        'Service',
        'Status',
        'Created Date',
        'Commission Amount (₹)',
        'Source',
        'Notes'
    ]);
}

// Add data rows
foreach ($leads as $lead) {
    $row = [
        $lead['id'],
        $lead['customer_name'],
        $lead['customer_phone'],
        $lead['customer_email'],
        $lead['service'],
    ];
    
    if ($export_type !== 'customers') {
        $row[] = ucfirst($lead['status']);
    }
    
    $row[] = $lead['created_date'];
    $row[] = number_format($lead['commission_amount'], 2);
    $row[] = $lead['source'];
    
    if ($export_type !== 'customers' && isset($lead['notes'])) {
        $row[] = $lead['notes'];
    } elseif ($export_type !== 'customers') {
        $row[] = '-';
    }
    
    fputcsv($output, $row);
}

fclose($output);

// ========== LOG ACTIVITY ==========
$checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
if (mysqli_num_rows($checkActivityTable) > 0) {
    $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'export_leads', ?, NOW())");
    if ($log_stmt) {
        $record_count = count($leads);
        $description = "Exported $record_count records";
        if ($export_type === 'customers') {
            $description = "Exported $record_count customers";
        } elseif ($status_filter !== 'all') {
            $description .= " with status '$status_filter'";
        }
        $description .= " from $from_date to $to_date";
        mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    }
}

mysqli_close($conn);
exit();
?>