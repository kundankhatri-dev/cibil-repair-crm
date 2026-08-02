<?php
// ============================================================
// CIBIL REPAIR CRM - Export Sales API (All Formats)
// Endpoint: /api/export_sales.php
// Method: GET
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== START OUTPUT BUFFERING =====
ob_start();

// ===== SET HEADERS =====
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
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
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GET PARAMETERS
// ============================================================

$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'csv';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$service = isset($_GET['service']) ? trim($_GET['service']) : '';
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;

// Validate format
$allowedFormats = ['csv', 'excel', 'json', 'pdf'];
if (!in_array($format, $allowedFormats)) {
    $format = 'csv';
}

// ============================================================
// BUILD QUERY
// ============================================================

$where = [];
$params = [];
$types = '';

// Search filter
if (!empty($search)) {
    $where[] = "(customer_name LIKE ? OR customer_email LIKE ? OR customer_phone LIKE ? OR service LIKE ?)";
    $searchWild = "%$search%";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
    $types .= 'ssss';
}

// Status filter
if (!empty($status) && $status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

// Service filter
if (!empty($service) && $service !== 'all') {
    $where[] = "service = ?";
    $params[] = $service;
    $types .= 's';
}

// Date range filter
if (!empty($from_date)) {
    $where[] = "DATE(sale_date) >= ?";
    $params[] = $from_date;
    $types .= 's';
}

if (!empty($to_date)) {
    $where[] = "DATE(sale_date) <= ?";
    $params[] = $to_date;
    $types .= 's';
}

// Build query
$query = "SELECT 
            s.id,
            s.lead_id,
            s.customer_name,
            s.customer_email,
            s.customer_phone,
            s.service,
            s.amount,
            s.commission_amount,
            s.partner_id,
            s.status,
            s.sale_date,
            s.notes,
            s.created_at,
            s.updated_at,
            p.name as partner_name,
            l.name as lead_name
          FROM sales s
          LEFT JOIN partners p ON s.partner_id = p.id
          LEFT JOIN leads l ON s.lead_id = l.id";

if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

$query .= " ORDER BY s.id DESC";

// Add limit if specified
if ($limit > 0) {
    $query .= " LIMIT ?";
    $params[] = $limit;
    $types .= 'i';
}

// ============================================================
// FETCH DATA
// ============================================================

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $query);
}

$sales = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $sales[] = $row;
    }
}
mysqli_close($conn);

if (empty($sales)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'No sales found to export']);
    exit();
}

// ============================================================
// PROCESS DATA FOR EXPORT
// ============================================================

$processedSales = [];
foreach ($sales as $sale) {
    $processedSales[] = [
        'id' => $sale['id'],
        'customer_name' => $sale['customer_name'],
        'customer_email' => $sale['customer_email'] ?? '',
        'customer_phone' => $sale['customer_phone'] ?? '',
        'service' => $sale['service'] ?? 'Written Off',
        'amount' => (float)$sale['amount'],
        'commission_amount' => (float)($sale['commission_amount'] ?? 0),
        'partner_id' => $sale['partner_id'] ?? '',
        'partner_name' => $sale['partner_name'] ?? '',
        'lead_id' => $sale['lead_id'] ?? '',
        'lead_name' => $sale['lead_name'] ?? '',
        'status' => $sale['status'] ?? 'Pending',
        'sale_date' => $sale['sale_date'] ?? '',
        'notes' => $sale['notes'] ?? '',
        'created_at' => $sale['created_at'] ?? '',
        'updated_at' => $sale['updated_at'] ?? ''
    ];
}

// ============================================================
// GENERATE EXPORT DATA
// ============================================================

// Prepare headers
$headers = [
    'Sale ID',
    'Customer Name',
    'Customer Email',
    'Customer Phone',
    'Service',
    'Amount (₹)',
    'Commission (₹)',
    'Partner ID',
    'Partner Name',
    'Lead ID',
    'Lead Name',
    'Status',
    'Sale Date',
    'Notes',
    'Created At',
    'Updated At'
];

// Prepare rows
$rows = [];
foreach ($processedSales as $sale) {
    $rows[] = [
        $sale['id'],
        $sale['customer_name'],
        $sale['customer_email'],
        $sale['customer_phone'],
        $sale['service'],
        number_format($sale['amount'], 2),
        number_format($sale['commission_amount'], 2),
        $sale['partner_id'],
        $sale['partner_name'],
        $sale['lead_id'],
        $sale['lead_name'],
        $sale['status'],
        $sale['sale_date'],
        $sale['notes'],
        $sale['created_at'],
        $sale['updated_at']
    ];
}

// ============================================================
// EXPORT HANDLING
// ============================================================

$filename = 'sales_export_' . date('Y-m-d_H-i-s');
ob_clean();

switch ($format) {
    case 'csv':
        exportCSV($headers, $rows, $filename);
        break;
        
    case 'excel':
        exportExcel($headers, $rows, $filename);
        break;
        
    case 'json':
        exportJSON($processedSales, $filename);
        break;
        
    case 'pdf':
        exportPDF($headers, $rows, $filename);
        break;
        
    default:
        exportCSV($headers, $rows, $filename);
}

// ============================================================
// EXPORT FUNCTIONS
// ============================================================

/**
 * Export as CSV
 */
function exportCSV($headers, $rows, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write rows
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * Export as Excel (.xls) using HTML table
 */
function exportExcel($headers, $rows, $filename) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" 
                  xmlns:x="urn:schemas-microsoft-com:office:excel" 
                  xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                <!--[if gte mso 9]>
                <xml>
                    <x:ExcelWorkbook>
                        <x:ExcelWorksheets>
                            <x:ExcelWorksheet>
                                <x:Name>Sales</x:Name>
                                <x:WorksheetOptions>
                                    <x:DisplayGridlines/>
                                </x:WorksheetOptions>
                            </x:ExcelWorksheet>
                        </x:ExcelWorksheets>
                    </x:ExcelWorkbook>
                </xml>
                <![endif]-->
                <style>
                    table {
                        border-collapse: collapse;
                        width: 100%;
                        font-family: Arial, sans-serif;
                        font-size: 10px;
                    }
                    th {
                        background-color: #0d9e78;
                        color: #ffffff;
                        font-weight: bold;
                        padding: 6px 4px;
                        border: 1px solid #0d7a5a;
                    }
                    td {
                        padding: 4px;
                        border: 1px solid #cccccc;
                    }
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    .status-completed {
                        color: #059669;
                        font-weight: bold;
                    }
                    .status-pending {
                        color: #d97706;
                        font-weight: bold;
                    }
                    .status-cancelled {
                        color: #dc2626;
                        font-weight: bold;
                    }
                    .amount {
                        font-weight: bold;
                    }
                </style>
            </head>
            <body>
                <h2>💰 Sales Export</h2>
                <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
                <p><strong>Total Records:</strong> ' . count($rows) . '</p>
                <table>
                    <thead>
                        <tr>';
    
    foreach ($headers as $header) {
        $html .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    
    $html .= '</tr></thead><tbody>';
    
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>
                <p style="margin-top:20px;color:#999;font-size:10px;">
                    Generated by CIBIL Repair CRM System
                </p>
            </body>
            </html>';
    
    echo $html;
    exit();
}

/**
 * Export as JSON
 */
function exportJSON($data, $filename) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $exportData = [
        'export_date' => date('Y-m-d H:i:s'),
        'total_records' => count($data),
        'data' => $data
    ];
    
    echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Export as PDF (HTML with print styles - user can print to PDF)
 */
function exportPDF($headers, $rows, $filename) {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.html"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Sales Export</title>
                <style>
                    @media print {
                        body { margin: 0; padding: 20px; }
                        .no-print { display: none; }
                    }
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 9px;
                        padding: 20px;
                    }
                    h1 {
                        color: #0d9e78;
                        border-bottom: 2px solid #0d9e78;
                        padding-bottom: 10px;
                    }
                    .info {
                        margin-bottom: 20px;
                        color: #666;
                        font-size: 12px;
                    }
                    table {
                        border-collapse: collapse;
                        width: 100%;
                        font-size: 8px;
                    }
                    th {
                        background-color: #0d9e78;
                        color: #ffffff;
                        font-weight: bold;
                        padding: 5px 3px;
                        text-align: left;
                        border: 1px solid #0d7a5a;
                    }
                    td {
                        padding: 3px;
                        border: 1px solid #cccccc;
                    }
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    .status-completed {
                        color: #059669;
                        font-weight: bold;
                    }
                    .status-pending {
                        color: #d97706;
                        font-weight: bold;
                    }
                    .status-cancelled {
                        color: #dc2626;
                        font-weight: bold;
                    }
                    .amount {
                        font-weight: bold;
                    }
                    .footer {
                        margin-top: 20px;
                        text-align: center;
                        color: #999;
                        font-size: 10px;
                        border-top: 1px solid #eee;
                        padding-top: 10px;
                    }
                </style>
            </head>
            <body>
                <h1>💰 Sales Export</h1>
                <div class="info">
                    <strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '<br>
                    <strong>Total Records:</strong> ' . count($rows) . '
                </div>
                <table>
                    <thead>
                        <tr>';
    
    foreach ($headers as $header) {
        $html .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    
    $html .= '</tr></thead><tbody>';
    
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>
                <div class="footer">
                    Generated by CIBIL Repair CRM System<br>
                    ' . date('Y-m-d H:i:s') . '
                </div>
                <div class="no-print" style="margin-top:20px;text-align:center;">
                    <button onclick="window.print()" style="padding:10px 30px;background:#0d9e78;color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:14px;">
                        🖨️ Print / Save as PDF
                    </button>
                </div>
            </body>
            </html>';
    
    echo $html;
    exit();
}
?>