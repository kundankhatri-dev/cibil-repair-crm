<?php
// api/partner/generate_report.php
// Partner Generate Report API - Generate various reports (leads, commission, performance, summary)

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
    if (isset($_GET['format']) && $_GET['format'] === 'csv') {
        header('HTTP/1.1 500 Internal Server Error');
        echo "Database connection failed";
    } else {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    }
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['format']) && $_GET['format'] === 'csv') {
        header('HTTP/1.1 401 Unauthorized');
        echo "Not authenticated";
    } else {
        echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    }
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
    if (isset($_GET['format']) && $_GET['format'] === 'csv') {
        header('HTTP/1.1 403 Forbidden');
        echo "Unauthorized access";
    } else {
        echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    }
    exit;
}

$partner_name = $role_data['name'];

// ========== DETERMINE LEADS TABLE ==========
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// ========== GET TABLE COLUMN NAMES ==========
$columns = [];
$colResult = mysqli_query($conn, "SHOW COLUMNS FROM $leadsTable");
if ($colResult) {
    while ($col = mysqli_fetch_assoc($colResult)) {
        $columns[] = $col['Field'];
    }
}

$nameCol = in_array('customer_name', $columns) ? 'customer_name' : (in_array('name', $columns) ? 'name' : 'customer_name');
$phoneCol = in_array('customer_phone', $columns) ? 'customer_phone' : (in_array('phone', $columns) ? 'phone' : 'customer_phone');
$emailCol = in_array('customer_email', $columns) ? 'customer_email' : (in_array('email', $columns) ? 'email' : 'customer_email');
$serviceCol = in_array('service_type', $columns) ? 'service_type' : (in_array('service', $columns) ? 'service' : 'service_type');
$commissionCol = in_array('commission_amount', $columns) ? 'commission_amount' : 'commission_amount';

// ========== GET INPUT PARAMETERS ==========
$report_type = isset($_GET['type']) ? $_GET['type'] : 'leads';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

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

// ========== VALIDATE REPORT TYPE ==========
$valid_types = ['leads', 'commission', 'performance', 'summary', 'monthly', 'status_summary'];
if (!in_array($report_type, $valid_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid report type. Allowed: leads, commission, performance, summary, monthly, status_summary']);
    exit;
}

// ========== GENERATE REPORT BASED ON TYPE ==========
$data = [];
$headers = [];
$filename = "";
$summary = [];

switch ($report_type) {
    case 'leads':
        $filename = "leads_report_{$from_date}_to_{$to_date}.csv";
        
        $query = "SELECT 
                    id,
                    $nameCol as customer_name,
                    $phoneCol as customer_phone,
                    COALESCE($emailCol, '-') as customer_email,
                    COALESCE($serviceCol, '-') as service,
                    status,
                    DATE_FORMAT(created_at, '%d-%m-%Y') as created_date,
                    COALESCE($commissionCol, 0) as commission
                  FROM $leadsTable 
                  WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ?
                  ORDER BY created_at DESC";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iss", $partner_id, $from_date, $to_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        
        $headers = ['Lead ID', 'Customer Name', 'Phone', 'Email', 'Service', 'Status', 'Date', 'Commission (₹)'];
        
        // Calculate summary
        $summary['total_leads'] = count($data);
        $summary['total_commission'] = array_sum(array_column($data, 'commission'));
        $summary['converted_count'] = count(array_filter($data, fn($d) => $d['status'] === 'converted'));
        break;
        
    case 'commission':
        $filename = "commission_report_{$from_date}_to_{$to_date}.csv";
        
        $query = "SELECT 
                    $nameCol as customer_name,
                    COALESCE($serviceCol, '-') as service,
                    $commissionCol as commission_amount,
                    DATE_FORMAT(created_at, '%d-%m-%Y') as converted_date,
                    CASE WHEN $commissionCol > 0 THEN 'Earned' ELSE 'Pending' END as status
                  FROM $leadsTable
                  WHERE partner_id = ? AND status = 'converted' AND DATE(created_at) BETWEEN ? AND ?
                  ORDER BY created_at DESC";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iss", $partner_id, $from_date, $to_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        
        $headers = ['Customer Name', 'Service', 'Commission Amount (₹)', 'Converted Date', 'Status'];
        
        $summary['total_commission'] = array_sum(array_column($data, 'commission_amount'));
        $summary['total_customers'] = count($data);
        break;
        
    case 'performance':
        $filename = "performance_report_{$from_date}_to_{$to_date}.csv";
        
        $query = "SELECT 
                    DATE_FORMAT(created_at, '%b %Y') as month,
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted,
                    ROUND((SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 2) as conversion_rate,
                    SUM($commissionCol) as total_commission
                  FROM $leadsTable 
                  WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ?
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY created_at ASC";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iss", $partner_id, $from_date, $to_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        
        $headers = ['Month', 'Total Leads', 'Converted', 'Conversion Rate (%)', 'Total Commission (₹)'];
        
        $summary['total_leads_period'] = array_sum(array_column($data, 'total_leads'));
        $summary['total_converted_period'] = array_sum(array_column($data, 'converted'));
        $summary['avg_conversion_rate'] = count($data) > 0 ? round(array_sum(array_column($data, 'conversion_rate')) / count($data), 2) : 0;
        break;
        
    case 'summary':
        $filename = "summary_report_{$from_date}_to_{$to_date}.csv";
        
        // Overall summary statistics
        $query = "SELECT 
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_leads,
                    SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted_leads,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_leads,
                    SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) as lost_leads,
                    SUM($commissionCol) as total_commission_earned
                  FROM $leadsTable 
                  WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iss", $partner_id, $from_date, $to_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $summary_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Service-wise breakdown
        $serviceQuery = "SELECT 
                            COALESCE($serviceCol, 'Other') as service,
                            COUNT(*) as count,
                            SUM($commissionCol) as commission
                         FROM $leadsTable 
                         WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ?
                         GROUP BY $serviceCol
                         ORDER BY count DESC";
        $serviceStmt = mysqli_prepare($conn, $serviceQuery);
        mysqli_stmt_bind_param($serviceStmt, "iss", $partner_id, $from_date, $to_date);
        mysqli_stmt_execute($serviceStmt);
        $serviceResult = mysqli_stmt_get_result($serviceStmt);
        $service_data = mysqli_fetch_all($serviceResult, MYSQLI_ASSOC);
        mysqli_stmt_close($serviceStmt);
        
        $data = [
            'summary' => $summary_data,
            'service_breakdown' => $service_data
        ];
        
        $headers = ['Metric', 'Value'];
        $summary['report_generated'] = date('Y-m-d H:i:s');
        break;
        
    case 'monthly':
        $filename = "monthly_trend_report.csv";
        
        $query = "SELECT 
                    DATE_FORMAT(created_at, '%b %Y') as month,
                    COUNT(*) as leads,
                    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as conversions,
                    SUM($commissionCol) as commission
                  FROM $leadsTable 
                  WHERE partner_id = ? 
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY created_at DESC
                  LIMIT 12";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $partner_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        
        $headers = ['Month', 'Leads', 'Conversions', 'Commission (₹)'];
        break;
        
    case 'status_summary':
        $filename = "status_summary_report.csv";
        
        $query = "SELECT 
                    status,
                    COUNT(*) as count,
                    SUM($commissionCol) as total_commission,
                    MIN(created_at) as first_record,
                    MAX(created_at) as last_record
                  FROM $leadsTable 
                  WHERE partner_id = ?
                  GROUP BY status
                  ORDER BY FIELD(status, 'new', 'contacted', 'converted', 'lost')";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $partner_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        
        $headers = ['Status', 'Count', 'Total Commission (₹)', 'First Record', 'Last Record'];
        break;
}

// ========== OUTPUT REPORT ==========
if ($format === 'json') {
    header('Content-Type: application/json');
    $response = [
        'success' => true,
        'report_type' => $report_type,
        'partner_name' => $partner_name,
        'period' => ['from' => $from_date, 'to' => $to_date],
        'generated_at' => date('Y-m-d H:i:s'),
        'data' => $data,
        'total_records' => is_array($data) && !isset($data['summary']) ? count($data) : (isset($data['summary']) ? 1 : 0)
    ];
    
    if (!empty($summary)) {
        $response['summary'] = $summary;
    }
    
    echo json_encode($response);
} else {
    // Output as CSV
    $filename = $report_type . "_report_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    header('Pragma: public');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Handle summary report specially
    if ($report_type === 'summary') {
        fputcsv($output, ['CIBIL Repair - Partner Performance Summary']);
        fputcsv($output, ['Partner Name:', $partner_name]);
        fputcsv($output, ['Report Period:', $from_date . ' to ' . $to_date]);
        fputcsv($output, ['Generated On:', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        if (isset($data['summary']) && $data['summary']) {
            fputcsv($output, ['OVERALL STATISTICS']);
            fputcsv($output, ['Total Leads', $data['summary']['total_leads'] ?? 0]);
            fputcsv($output, ['New Leads', $data['summary']['new_leads'] ?? 0]);
            fputcsv($output, ['Contacted Leads', $data['summary']['contacted_leads'] ?? 0]);
            fputcsv($output, ['Converted Leads', $data['summary']['converted_leads'] ?? 0]);
            fputcsv($output, ['Lost Leads', $data['summary']['lost_leads'] ?? 0]);
            fputcsv($output, ['Total Commission Earned (₹)', number_format($data['summary']['total_commission_earned'] ?? 0, 2)]);
            fputcsv($output, []);
        }
        
        if (!empty($data['service_breakdown'])) {
            fputcsv($output, ['SERVICE WISE BREAKDOWN']);
            fputcsv($output, ['Service', 'Count', 'Commission (₹)']);
            foreach ($data['service_breakdown'] as $row) {
                fputcsv($output, [$row['service'], $row['count'], number_format($row['commission'], 2)]);
            }
        }
    } else {
        // Add headers
        fputcsv($output, $headers);
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }
        
        // Add summary footer for certain reports
        if ($report_type === 'leads' && !empty($summary)) {
            fputcsv($output, []);
            fputcsv($output, ['SUMMARY']);
            fputcsv($output, ['Total Leads', $summary['total_leads']]);
            fputcsv($output, ['Converted', $summary['converted_count']]);
            fputcsv($output, ['Total Commission (₹)', number_format($summary['total_commission'], 2)]);
            $conversion_rate = $summary['total_leads'] > 0 ? round(($summary['converted_count'] / $summary['total_leads']) * 100, 2) : 0;
            fputcsv($output, ['Conversion Rate', $conversion_rate . '%']);
        } elseif ($report_type === 'commission' && !empty($summary)) {
            fputcsv($output, []);
            fputcsv($output, ['SUMMARY']);
            fputcsv($output, ['Total Customers', $summary['total_customers']]);
            fputcsv($output, ['Total Commission (₹)', number_format($summary['total_commission'], 2)]);
        }
    }
    
    fclose($output);
}

// ========== LOG ACTIVITY ==========
$checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
if (mysqli_num_rows($checkActivityTable) > 0 && $format !== 'csv') {
    $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'generate_report', ?, NOW())");
    if ($log_stmt) {
        $description = "Generated $report_type report for period $from_date to $to_date";
        mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    }
}

mysqli_close($conn);
?>