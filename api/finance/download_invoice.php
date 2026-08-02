<?php
// ============================================================
// DOWNLOAD INVOICE
// ============================================================
session_start();

// Authentication
$allowed_roles = ['finance_team', 'admin', 'manager', 'super_admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    die('Unauthorized access');
}

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$invoice_id) {
    die('Invalid invoice ID');
}

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    die('Database connection failed');
}

// Fetch invoice with client details
$query = "SELECT i.*, c.name as client_name, c.email as client_email, c.phone as client_phone 
          FROM invoices i 
          LEFT JOIN clients c ON i.client_id = c.id 
          WHERE i.id = $invoice_id";
$result = mysqli_query($conn, $query);
$invoice = mysqli_fetch_assoc($result);

if (!$invoice) {
    die('Invoice not found');
}

// Set headers for file download
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="INVOICE_' . $invoice['invoice_no'] . '.html"');

// Output invoice HTML
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= $invoice['invoice_no'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .invoice { max-width: 800px; margin: auto; border: 1px solid #ddd; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #0d9e78; padding-bottom: 10px; margin-bottom: 20px; }
        .company { font-size: 24px; font-weight: bold; color: #0d9e78; }
        .title { font-size: 20px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .total { font-weight: bold; font-size: 16px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="header">
            <div class="company">CIBIL Repair</div>
            <div>Mumbai, India | contact@cibilrepair.in</div>
        </div>

        <div class="title">TAX INVOICE</div>

        <table>
            <tr>
                <td width="50%">
                    <strong>Invoice No:</strong> <?= $invoice['invoice_no'] ?><br>
                    <strong>Date:</strong> <?= date('d-m-Y', strtotime($invoice['invoice_date'])) ?><br>
                    <strong>Due Date:</strong> <?= date('d-m-Y', strtotime($invoice['due_date'])) ?>
                </td>
                <td width="50%">
                    <strong>Bill To:</strong><br>
                    <?= htmlspecialchars($invoice['client_name']) ?><br>
                    <?= htmlspecialchars($invoice['client_email'] ?? '') ?>
                </td>
            </tr>
        </table>

        <table>
            <thead><tr><th>Description</th><th>Amount (₹)</th></tr></thead>
            <tbody>
                <tr><td><?= htmlspecialchars($invoice['package_name'] ?? 'Service') ?></td><td><?= number_format($invoice['amount'], 2) ?></td></tr>
                <tr><td>GST (18%)</td><td><?= number_format($invoice['gst'], 2) ?></td></tr>
                <tr style="border-top:2px solid #ddd;"><td><strong>Total</strong></td><td><strong>₹<?= number_format($invoice['total'], 2) ?></strong></td></tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is a computer generated invoice.</p>
        </div>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
?>