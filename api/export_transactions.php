<?php
// ============================================================
// CIBIL REPAIR CRM - Export Transactions API (All Formats)
// Endpoint: /api/export_transactions.php
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
// GST CONFIGURATION
// ============================================================

define('GST_RATE', 18);
define('GST_CGST', 9);
define('GST_SGST', 9);

// ============================================================
// GET PARAMETERS
// ============================================================

$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'csv';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$method = isset($_GET['method']) ? trim($_GET['method']) : '';
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
    $where[] = "(description LIKE ? OR reference_id LIKE ?)";
    $searchWild = "%$search%";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $types .= 'ss';
}

// Type filter
if (!empty($type) && $type !== 'all') {
    $where[] = "type = ?";
    $params[] = $type;
    $types .= 's';
}

// Method filter
if (!empty($method) && $method !== 'all') {
    $where[] = "method = ?";
    $params[] = $method;
    $types .= 's';
}

// Date range filter
if (!empty($from_date)) {
    $where[] = "DATE(date) >= ?";
    $params[] = $from_date;
    $types .= 's';
}

if (!empty($to_date)) {
    $where[] = "DATE(date) <= ?";
    $params[] = $to_date;
    $types .= 's';
}

// Build query
$query = "SELECT 
            id,
            date,
            description,
            amount,
            type,
            method,
            fee_amount,
            gst_amount,
            cgst_amount,
            sgst_amount,
            total_amount,
            reference_id,
            customer_id,
            partner_id,
            balance_after,
            created_at,
            updated_at
          FROM transactions";

if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

$query .= " ORDER BY id DESC";

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

$transactions = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
}
mysqli_close($conn);

if (empty($transactions)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'No transactions found to export']);
    exit();
}

// ============================================================
// PROCESS DATA FOR EXPORT
// ============================================================

$processedTransactions = [];
foreach ($transactions as $tx) {
    $processedTransactions[] = [
        'id' => $tx['id'],
        'date' => $tx['date'],
        'description' => $tx['description'],
        'amount' => (float)$tx['amount'],
        'type' => ucfirst($tx['type']),
        'method' => $tx['method'] ?? 'Cash',
        'fee_amount' => (float)($tx['fee_amount'] ?? 0),
        'gst_amount' => (float)($tx['gst_amount'] ?? 0),
        'cgst_amount' => (float)($tx['cgst_amount'] ?? 0),
        'sgst_amount' => (float)($tx['sgst_amount'] ?? 0),
        'total_amount' => (float)($tx['total_amount'] ?? $tx['amount']),
        'reference_id' => $tx['reference_id'] ?? '',
        'customer_id' => $tx['customer_id'] ?? '',
        'partner_id' => $tx['partner_id'] ?? '',
        'balance_after' => (float)($tx['balance_after'] ?? 0),
        'created_at' => $tx['created_at'] ?? '',
        'updated_at' => $tx['updated_at'] ?? ''
    ];
}

// ============================================================
// GENERATE EXPORT DATA
// ============================================================

// Prepare headers
$headers = [
    'Transaction ID',
    'Date',
    'Description',
    'Amount (₹)',
    'Type',
    'Payment Method',
    'Fee Amount (₹)',
    'GST Amount (₹)',
    'CGST (₹)',
    'SGST (₹)',
    'Total Amount (₹)',
    'Reference ID',
    'Customer ID',
    'Partner ID',
    'Balance After (₹)',
    'Created At',
    'Updated At'
];

// Prepare rows
$rows = [];
foreach ($processedTransactions as $tx) {
    $rows[] = [
        $tx['id'],
        $tx['date'],
        $tx['description'],
        number_format($tx['amount'], 2),
        $tx['type'],
        $tx['method'],
        number_format($tx['fee_amount'], 2),
        number_format($tx['gst_amount'], 2),
        number_format($tx['cgst_amount'], 2),
        number_format($tx['sgst_amount'], 2),
        number_format($tx['total_amount'], 2),
        $tx['reference_id'],
        $tx['customer_id'],
        $tx['partner_id'],
        number_format($tx['balance_after'], 2),
        $tx['created_at'],
        $tx['updated_at']
    ];
}

// ============================================================
// EXPORT HANDLING
// ============================================================

$filename = 'transactions_export_' . date('Y-m-d_H-i-s');
ob_clean();

switch ($format) {
    case 'csv':
        exportCSV($headers, $rows, $filename);
        break;
        
    case 'excel':
        exportExcel($headers, $rows, $filename);
        break;
        
    case 'json':
        exportJSON($processedTransactions, $filename);
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
                                <x:Name>Transactions</x:Name>
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
                    .credit {
                        color: #059669;
                        font-weight: bold;
                    }
                    .debit {
                        color: #dc2626;
                        font-weight: bold;
                    }
                    .amount {
                        font-weight: bold;
                    }
                </style>
            </head>
            <body>
                <h2>💰 Transactions Export</h2>
                <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
                <p><strong>Total Records:</strong> ' . count($rows) . '</p>
                <p><strong>GST Rate:</strong> ' . GST_RATE . '% (CGST: ' . GST_CGST . '%, SGST: ' . GST_SGST . '%)</p>
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
        'gst_rate' => GST_RATE,
        'cgst_rate' => GST_CGST,
        'sgst_rate' => GST_SGST,
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
                <title>Transactions Export</title>
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
                    .credit {
                        color: #059669;
                        font-weight: bold;
                    }
                    .debit {
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
                <h1>💰 Transactions Export</h1>
                <div class="info">
                    <strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '<br>
                    <strong>Total Records:</strong> ' . count($rows) . '<br>
                    <strong>GST Rate:</strong> ' . GST_RATE . '% (CGST: ' . GST_CGST . '%, SGST: ' . GST_SGST . '%)
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