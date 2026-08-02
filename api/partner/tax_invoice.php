<?php
// api/partner/tax_invoice.php
// Generate GST-compliant tax invoice

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$partner_id = $_SESSION['user_id'];
$invoice_id = $_GET['id'] ?? 0;
$format = $_GET['format'] ?? 'html'; // html, pdf, json

// Get commission data
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

$query = "SELECT 
    l.id, l.customer_name, l.service_type, l.commission_amount, l.created_at,
    u.name as partner_name, u.email as partner_email, u.phone as partner_phone,
    p.company_name, p.gst_number
    FROM $leadsTable l
    JOIN users u ON l.partner_id = u.id
    LEFT JOIN partners p ON l.partner_id = p.user_id
    WHERE l.id = ? AND l.partner_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $invoice_id, $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($stmt);

if (!$data) {
    die("Invoice not found");
}

$invoice_no = 'INV-' . date('Ymd') . '-' . str_pad($data['id'], 4, '0', STR_PAD_LEFT);
$gst_rate = 18;
$subtotal = $data['commission_amount'];
$gst_amount = $subtotal * $gst_rate / 100;
$total = $subtotal + $gst_amount;

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice #' . $invoice_no . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #1f8a72; margin: 0; }
        .company-info, .customer-info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #1f8a72; color: white; }
        .total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 20px; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <h1>CIBIL Repair</h1>
            <p>GST Invoice</p>
        </div>
        
        <div class="company-info">
            <strong>CIBIL Repair</strong><br>
            123 Business Park, Mumbai - 400001<br>
            GSTIN: 27AAACC1234F1Z<br>
            PAN: AAACC1234F
        </div>
        
        <div class="customer-info">
            <strong>Bill To:</strong><br>
            ' . htmlspecialchars($data['partner_name']) . '<br>
            ' . htmlspecialchars($data['partner_email']) . '<br>
            ' . htmlspecialchars($data['partner_phone']) . '
        </div>
        
        <div>
            <strong>Invoice No:</strong> ' . $invoice_no . '<br>
            <strong>Date:</strong> ' . date('d-m-Y') . '<br>
            <strong>Due Date:</strong> ' . date('d-m-Y', strtotime('+15 days')) . '
        </div>
        
        <table>
            <thead>
                <tr><th>Description</th><th>Amount (₹)</th><th>GST (18%)</th><th>Total (₹)</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Commission for ' . htmlspecialchars($data['service_type']) . '<br><small>Customer: ' . htmlspecialchars($data['customer_name']) . '</small></td>
                    <td>' . number_format($subtotal, 2) . '</td>
                    <td>' . number_format($gst_amount, 2) . '</td>
                    <td>' . number_format($total, 2) . '</td>
                </tr>
            </tbody>
        </table>
        
        <div class="total">
            Total Amount: ₹' . number_format($total, 2) . '
        </div>
        
        <div class="footer">
            This is a computer-generated invoice. No signature required.<br>
            For any queries, contact support@cibilrepair.in
        </div>
    </div>
</body>
</html>';

if ($format === 'json') {
    echo json_encode([
        'success' => true,
        'invoice_no' => $invoice_no,
        'partner_name' => $data['partner_name'],
        'amount' => $subtotal,
        'gst' => $gst_amount,
        'total' => $total,
        'date' => date('Y-m-d')
    ]);
} else {
    echo $html;
}

mysqli_close($conn);
?>