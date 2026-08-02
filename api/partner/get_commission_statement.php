<?php
// api/partner/get_commission_statement.php
// PDF commission statement download

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$partner_id = $_SESSION['user_id'];
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly'; // monthly, yearly, custom
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get partner details
$stmt = mysqli_prepare($conn, "SELECT name, email, phone FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$partner = mysqli_fetch_assoc($stmt);

// Get commission data
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

$date_condition = "";
if ($period === 'monthly') {
    $date_condition = "AND MONTH(created_at) = $month AND YEAR(created_at) = $year";
} elseif ($period === 'yearly') {
    $date_condition = "AND YEAR(created_at) = $year";
}

$query = "SELECT customer_name, service_type, commission_amount, created_at 
          FROM $leadsTable 
          WHERE partner_id = ? AND status = 'converted' $date_condition
          ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$commissions = mysqli_fetch_all($result, MYSQLI_ASSOC);

$total_commission = array_sum(array_column($commissions, 'commission_amount'));

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="commission_statement_' . date('Y-m-d') . '.pdf"');

// Generate HTML for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Commission Statement</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #1f8a72; margin-bottom: 5px; }
        .partner-info { margin-bottom: 30px; padding: 15px; background: #f5f5f5; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #1f8a72; color: white; }
        .total { margin-top: 20px; text-align: right; font-size: 18px; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CIBIL Repair</h1>
        <p>Commission Statement</p>
    </div>
    
    <div class="partner-info">
        <strong>Partner:</strong> ' . htmlspecialchars($partner['name']) . '<br>
        <strong>Email:</strong> ' . htmlspecialchars($partner['email']) . '<br>
        <strong>Period:</strong> ' . ucfirst($period) . ' - ' . ($period === 'monthly' ? date('F Y', mktime(0,0,0,$month,1,$year)) : $year) . '<br>
        <strong>Generated On:</strong> ' . date('d-m-Y H:i:s') . '
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Customer Name</th>
                <th>Service</th>
                <th>Commission Amount</th>
            </tr>
        </thead>
        <tbody>';

if (empty($commissions)) {
    $html .= '<tr><td colspan="4" style="text-align:center;">No commission records found</td></tr>';
} else {
    foreach ($commissions as $comm) {
        $html .= '
            <tr>
                <td>' . date('d-m-Y', strtotime($comm['created_at'])) . '</td>
                <td>' . htmlspecialchars($comm['customer_name']) . '</td>
                <td>' . htmlspecialchars($comm['service_type']) . '</td>
                <td>₹' . number_format($comm['commission_amount'], 2) . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
     </table>
     
     <div class="total">
         Total Commission: ₹' . number_format($total_commission, 2) . '
     </div>
     
     <div class="footer">
         This is a system-generated statement. For any queries, please contact support@cibilrepair.in
     </div>
</body>
</html>';

// Use a library like dompdf or TCPDF to convert HTML to PDF
// For now, output HTML (replace with actual PDF generation in production)
echo $html;

mysqli_close($conn);
?>