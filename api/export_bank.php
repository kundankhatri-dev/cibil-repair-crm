<?php
// ============================================================
// CIBIL REPAIR CRM - Export Bank API
// Endpoint: /api/export_bank.php
// Method: GET
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// SESSION & AUTHENTICATION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// ============================================================
// GET PARAMETERS
// ============================================================

$format = isset($_GET['format']) ? trim($_GET['format']) : 'csv';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ============================================================
// BUILD QUERY
// ============================================================

$where = [];
$params = [];
$types = '';

if ($id > 0) {
    $where[] = "id = ?";
    $params[] = $id;
    $types .= 'i';
}

if (!empty($search)) {
    $where[] = "(name LIKE ? OR contact LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ssss';
}

if (!empty($status) && $status !== 'all') {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ============================================================
// GET BANKS DATA
// ============================================================

$sql = "SELECT id, name, contact, email, phone, status, notes, created_at FROM banks $whereClause ORDER BY name ASC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$banks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $banks[] = $row;
}
mysqli_stmt_close($stmt);

// ============================================================
// CHECK IF BANKS EXIST
// ============================================================

if (empty($banks)) {
    echo json_encode(['success' => false, 'error' => 'No banks found to export']);
    exit;
}

// ============================================================
// EXPORT BASED ON FORMAT
// ============================================================

$filename = 'bank_export_' . date('Y-m-d_H-i-s');

switch ($format) {
    case 'csv':
        exportCSV($banks, $filename);
        break;
    case 'excel':
        exportExcel($banks, $filename);
        break;
    case 'json':
        exportJSON($banks, $filename);
        break;
    case 'pdf':
        exportPDF($banks, $filename);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid format. Supported: csv, excel, json, pdf']);
        exit;
}

mysqli_close($conn);

// ============================================================
// EXPORT FUNCTIONS
// ============================================================

function exportCSV($banks, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");
    
    fputcsv($output, ['ID', 'Bank Name', 'Contact Person', 'Email', 'Phone', 'Status', 'Notes', 'Created Date']);
    
    foreach ($banks as $bank) {
        fputcsv($output, [
            $bank['id'],
            $bank['name'],
            $bank['contact'] ?? '',
            $bank['email'] ?? '',
            $bank['phone'] ?? '',
            $bank['status'] ?? 'active',
            $bank['notes'] ?? '',
            $bank['created_at'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}

function exportExcel($banks, $filename) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8"></head><body>';
    echo '<h2>Banks Export</h2>';
    echo '<p>Exported on: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<table border="1">';
    echo '<tr style="background:#0d9e78;color:#fff;">';
    echo '<th>ID</th><th>Bank Name</th><th>Contact Person</th><th>Email</th><th>Phone</th><th>Status</th><th>Notes</th><th>Created Date</th>';
    echo '</tr>';
    
    foreach ($banks as $bank) {
        echo '<tr>';
        echo '<td>' . $bank['id'] . '</td>';
        echo '<td>' . htmlspecialchars($bank['name']) . '</td>';
        echo '<td>' . htmlspecialchars($bank['contact'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($bank['email'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($bank['phone'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($bank['status'] ?? 'active') . '</td>';
        echo '<td>' . htmlspecialchars($bank['notes'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($bank['created_at'] ?? '') . '</td>';
        echo '</tr>';
    }
    
    echo '</table></body></html>';
    exit;
}

function exportJSON($banks, $filename) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    
    echo json_encode([
        'export_date' => date('Y-m-d H:i:s'),
        'total_banks' => count($banks),
        'banks' => $banks
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function exportPDF($banks, $filename) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    ?>
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"><title>Banks Export</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #0d9e78; text-align: center; }
        .info { text-align: center; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0d9e78; color: #fff; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f9f9f9; }
        .footer { text-align: center; margin-top: 30px; color: #999; font-size: 10px; }
    </style>
    </head>
    <body>
        <h1>Banks Export</h1>
        <div class="info">Exported on: <?php echo date('Y-m-d H:i:s'); ?> | Total: <?php echo count($banks); ?></div>
        <table>
            <tr><th>ID</th><th>Bank Name</th><th>Contact</th><th>Email</th><th>Phone</th><th>Status</th></tr>
            <?php foreach ($banks as $bank): ?>
            <tr>
                <td><?php echo $bank['id']; ?></td>
                <td><?php echo htmlspecialchars($bank['name']); ?></td>
                <td><?php echo htmlspecialchars($bank['contact'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($bank['email'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($bank['phone'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($bank['status'] ?? 'active'); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <div class="footer">Generated by CIBIL Repair CRM</div>
    </body>
    </html>
    <?php
    exit;
}
?>