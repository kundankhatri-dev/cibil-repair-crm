<?php
// ============================================================
// EXPORT LEADS TO CSV
// ============================================================
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="leads_' . date('Y-m-d') . '.csv"');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Security: Check referer
$allowed_referers = ['https://cibilrepair.in', 'http://localhost', 'https://yourdomain.com'];
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$referer_ok = false;
foreach ($allowed_referers as $allowed) {
    if (strpos($referer, $allowed) !== false) {
        $referer_ok = true;
        break;
    }
}
// if (!$referer_ok) { die('Unauthorized'); }

// Include database config
require_once '../config/database.php';
session_start();

// Verify admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized access');
}

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers (UTF-8 with BOM for Excel compatibility)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, [
    'ID',
    'Name',
    'Phone',
    'Email',
    'Message',
    'Created Date',
    'Status'
]);

// Get leads from database
$result = $conn->query("SELECT id, name, phone, email, message, created_at, status FROM leads ORDER BY id DESC");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['phone'],
            $row['email'],
            $row['message'],
            date('d-m-Y H:i:s', strtotime($row['created_at'])),
            $row['status'] ?? 'New'
        ]);
    }
} else {
    // Add sample data if no leads exist
    fputcsv($output, ['1', 'Rajesh Kumar', '9876543210', 'rajesh@example.com', 'Need CIBIL repair', date('d-m-Y H:i:s'), 'New']);
    fputcsv($output, ['2', 'Priya Sharma', '9876543211', 'priya@example.com', 'Credit score issue', date('d-m-Y H:i:s', strtotime('-1 day')), 'Contacted']);
    fputcsv($output, ['3', 'Amit Singh', '9876543212', 'amit@example.com', 'Loan settlement query', date('d-m-Y H:i:s', strtotime('-2 days')), 'In Progress']);
}

// Close output stream
fclose($output);

// Log the export activity
$log_query = "INSERT INTO activity_logs (user_id, user_name, action, details, ip_address) 
              VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['user_name'] . "', 'Export Leads', 'Exported leads to CSV', '" . $_SERVER['REMOTE_ADDR'] . "')";
$conn->query($log_query);

exit;
?>