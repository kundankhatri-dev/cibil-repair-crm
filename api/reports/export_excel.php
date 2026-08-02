<?php
// api/reports/export_excel.php
require_once '../config.php';
require_once 'config.php';

session_start();

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user_role'] ?? '';

if (!$user_id) {
    die('Unauthorized');
}

$type = $_GET['type'] ?? 'leads';
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$format = $_GET['format'] ?? 'csv'; // csv or xlsx

// Validate dates
$from_date = date('Y-m-d', strtotime($from_date));
$to_date = date('Y-m-d', strtotime($to_date));

// Validate report type
$valid_types = ['leads', 'payments', 'users', 'commission', 'payouts', 'customers'];
if (!in_array($type, $valid_types)) {
    $type = 'leads';
}

// Use prepared statements for security
$query = "";
$params = [];
$types = "";

switch ($type) {
    case 'leads':
        $query = "SELECT id, customer_name, customer_phone, COALESCE(service_type, service) as service, status, DATE_FORMAT(created_at, '%d-%m-%Y') as date, COALESCE(commission_amount, 0) as commission 
                  FROM partner_leads 
                  WHERE partner_id = ? AND DATE(created_at) BETWEEN ? AND ? 
                  ORDER BY created_at DESC";
        $params = [$user_id, $from_date, $to_date];
        $types = "iss";
        $headers = ['ID', 'Customer Name', 'Phone', 'Service', 'Status', 'Date', 'Commission (₹)'];
        break;
        
    case 'commission':
        $query = "SELECT l.customer_name, l.service_type as service, l.commission_amount, DATE_FORMAT(l.created_at, '%d-%m-%Y') as date, 'Earned' as status
                  FROM partner_leads l
                  WHERE l.partner_id = ? AND l.status = 'converted' AND DATE(l.created_at) BETWEEN ? AND ?
                  ORDER BY l.created_at DESC";
        $params = [$user_id, $from_date, $to_date];
        $types = "iss";
        $headers = ['Customer Name', 'Service', 'Commission Amount (₹)', 'Date', 'Status'];
        break;
        
    case 'payouts':
        $query = "SELECT id, amount, status, DATE_FORMAT(request_date, '%d-%m-%Y') as request_date, DATE_FORMAT(paid_date, '%d-%m-%Y') as paid_date, transaction_id
                  FROM partner_payouts
                  WHERE partner_id = ? AND DATE(request_date) BETWEEN ? AND ?
                  ORDER BY request_date DESC";
        $params = [$user_id, $from_date, $to_date];
        $types = "iss";
        $headers = ['Payout ID', 'Amount (₹)', 'Status', 'Request Date', 'Paid Date', 'Transaction ID'];
        break;
        
    case 'customers':
        $query = "SELECT id, customer_name, customer_phone, customer_email, COALESCE(service_type, service) as service, DATE_FORMAT(created_at, '%d-%m-%Y') as joined_date
                  FROM partner_leads
                  WHERE partner_id = ? AND status = 'converted' AND DATE(created_at) BETWEEN ? AND ?
                  ORDER BY created_at DESC";
        $params = [$user_id, $from_date, $to_date];
        $types = "iss";
        $headers = ['ID', 'Customer Name', 'Phone', 'Email', 'Service', 'Joined Date'];
        break;
        
    case 'payments':
    default:
        $query = "SELECT p.transaction_id, p.amount, p.status, DATE_FORMAT(p.created_at, '%d-%m-%Y') as date, u.name as user_name
                  FROM payments p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.user_id = ? AND DATE(p.created_at) BETWEEN ? AND ?
                  ORDER BY p.created_at DESC";
        $params = [$user_id, $from_date, $to_date];
        $types = "iss";
        $headers = ['Transaction ID', 'Amount (₹)', 'Status', 'Date', 'User'];
        break;
}

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

if ($format === 'xlsx' && class_exists('XLSXWriter')) {
    // Excel format
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $type . '_report_' . date('Y-m-d') . '.xlsx"');
    
    $writer = new XLSXWriter();
    $writer->writeSheetRow('Sheet1', $headers);
    foreach ($data as $row) {
        $writer->writeSheetRow('Sheet1', array_values($row));
    }
    $writer->writeToStdOut();
} else {
    // CSV format
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $type . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($output, $headers);
    foreach ($data as $row) {
        fputcsv($output, array_values($row));
    }
    fclose($output);
}

mysqli_close($conn);
?>