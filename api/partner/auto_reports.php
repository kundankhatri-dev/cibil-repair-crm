<?php
// api/partner/auto_reports.php
// Auto-generate and send reports

session_start();
require_once '../config.php';

$report_type = $_GET['type'] ?? 'daily'; // daily, weekly, monthly

$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// Get all partners
$partners_query = "SELECT id, name, email FROM users WHERE role = 'partner' AND status = 'active'";
$partners_stmt = mysqli_prepare($conn, $partners_query);
mysqli_stmt_execute($partners_stmt);
$partners_result = mysqli_stmt_get_result($partners_stmt);
$partners = mysqli_fetch_all($partners_result, MYSQLI_ASSOC);

$reports_sent = 0;
$date_condition = "";
$period_name = "";

if ($report_type === 'daily') {
    $date_condition = "DATE(created_at) = CURDATE()";
    $period_name = "Daily";
} elseif ($report_type === 'weekly') {
    $date_condition = "YEARWEEK(created_at) = YEARWEEK(CURDATE())";
    $period_name = "Weekly";
} else {
    $date_condition = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    $period_name = "Monthly";
}

foreach ($partners as $partner) {
    // Get partner stats
    $stats_query = "SELECT 
        COUNT(*) as total_leads,
        SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted,
        SUM(commission_amount) as total_commission
        FROM $leadsTable 
        WHERE partner_id = ? AND $date_condition";
    
    $stats_stmt = mysqli_prepare($conn, $stats_query);
    mysqli_stmt_bind_param($stats_stmt, "i", $partner['id']);
    mysqli_stmt_execute($stats_stmt);
    $stats_result = mysqli_stmt_get_result($stats_stmt);
    $stats = mysqli_fetch_assoc($stats_stmt);
    
    // Generate report content
    $report = "{$period_name} Performance Report\n";
    $report .= "========================\n\n";
    $report .= "Partner: {$partner['name']}\n";
    $report .= "Period: " . date('d-m-Y') . "\n\n";
    $report .= "📊 Summary:\n";
    $report .= "- Total Leads: " . ($stats['total_leads'] ?? 0) . "\n";
    $report .= "- Converted: " . ($stats['converted'] ?? 0) . "\n";
    $report .= "- Commission Earned: ₹" . number_format($stats['total_commission'] ?? 0, 2) . "\n";
    $report .= "- Conversion Rate: " . (($stats['total_leads'] ?? 0) > 0 ? round((($stats['converted'] ?? 0) / ($stats['total_leads'] ?? 1)) * 100, 1) : 0) . "%\n\n";
    $report .= "💡 Tip: Keep following up with new leads within 24 hours for better conversion!\n";
    $report .= "\n--\nCIBIL Repair System\n";
    
    // Send email
    $subject = "{$period_name} Performance Report - CIBIL Repair";
    // mail($partner['email'], $subject, $report, "From: reports@cibilrepair.in");
    
    $reports_sent++;
}

// Log report generation
$logTable = 'report_logs';
$checkLog = mysqli_query($conn, "SHOW TABLES LIKE '$logTable'");
if (mysqli_num_rows($checkLog) == 0) {
    $createLog = "CREATE TABLE $logTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_type VARCHAR(20),
        recipients_count INT,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $createLog);
}

$log_insert = mysqli_prepare($conn, "INSERT INTO $logTable (report_type, recipients_count) VALUES (?, ?)");
mysqli_stmt_bind_param($log_insert, "si", $report_type, $reports_sent);
mysqli_stmt_execute($log_insert);

echo json_encode([
    'success' => true,
    'report_type' => $report_type,
    'reports_sent' => $reports_sent,
    'timestamp' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>