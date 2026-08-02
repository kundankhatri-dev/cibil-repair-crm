<?php
// ============================================================
// CIBIL REPAIR CRM - Export Customers API (All Formats)
// Endpoint: /api/export_customers.php
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
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)";
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

// Build query
$query = "SELECT 
            id,
            name,
            email,
            phone,
            city,
            service,
            status,
            joined,
            notes,
            created_at,
            updated_at
          FROM customers";

if (!empty($where)) {
    $query .= " WHERE " . implode(' AND ', $where);
}

$query .= " ORDER BY id DESC";

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

$customers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $customers[] = $row;
    }
}
mysqli_close($conn);

if (empty($customers)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'No customers found to export']);
    exit();
}

// ============================================================
// GENERATE EXPORT DATA
// ============================================================

// Prepare headers
$headers = [
    'ID',
    'Name',
    'Email',
    'Phone',
    'City',
    'Service',
    'Status',
    'Joined Date',
    'Notes',
    'Created At',
    'Updated At'
];

// Prepare rows
$rows = [];
foreach ($customers as $customer) {
    $rows[] = [
        $customer['id'],
        $customer['name'],
        $customer['email'],
        $customer['phone'] ?? '',
        $customer['city'] ?? '',
        $customer['service'] ?? 'Written Off',
        ucfirst($customer['status'] ?? 'active'),
        $customer['joined'] ?? '',
        $customer['notes'] ?? '',
        $customer['created_at'] ?? '',
        $customer['updated_at'] ?? ''
    ];
}

// ============================================================
// EXPORT HANDLING
// ============================================================

$filename = 'customers_export_' . date('Y-m-d_H-i-s');
ob_clean();

switch ($format) {
    case 'csv':
        exportCSV($headers, $rows, $filename);
        break;
        
    case 'excel':
        exportExcel($headers, $rows, $filename);
        break;
        
    case 'json':
        exportJSON($customers, $filename);
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
                                <x:Name>Customers</x:Name>
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
                        font-size: 11px;
                    }
                    th {
                        background-color: #0d9e78;
                        color: #ffffff;
                        font-weight: bold;
                        padding: 8px;
                        border: 1px solid #0d7a5a;
                    }
                    td {
                        padding: 6px 8px;
                        border: 1px solid #cccccc;
                    }
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    .status-active {
                        color: #059669;
                    }
                    .status-inactive {
                        color: #dc2626;
                    }
                </style>
            </head>
            <body>
                <h2>📋 Customers Export</h2>
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
                <title>Customers Export</title>
                <style>
                    @media print {
                        body { margin: 0; padding: 20px; }
                        .no-print { display: none; }
                    }
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 11px;
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
                        font-size: 13px;
                    }
                    table {
                        border-collapse: collapse;
                        width: 100%;
                        font-size: 10px;
                    }
                    th {
                        background-color: #0d9e78;
                        color: #ffffff;
                        font-weight: bold;
                        padding: 8px 6px;
                        text-align: left;
                        border: 1px solid #0d7a5a;
                    }
                    td {
                        padding: 6px;
                        border: 1px solid #cccccc;
                    }
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    .status-active {
                        color: #059669;
                        font-weight: bold;
                    }
                    .status-inactive {
                        color: #dc2626;
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
                <h1>📋 Customers Export</h1>
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