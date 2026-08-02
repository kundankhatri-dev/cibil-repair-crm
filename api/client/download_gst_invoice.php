<?php
// api/client/download_gst_invoice.php - Download GST Invoice as PDF
session_start();
require_once('TCPDF-main/tcpdf.php'); // You'll need to install TCPDF

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;

if (!$invoice_id || !$client_id) {
    die('Invalid request');
}

// Fetch invoice details
$query = "SELECT i.*, u.name as client_name, u.email as client_email, u.phone as client_phone
          FROM gst_invoices i
          JOIN users u ON i.client_id = u.id
          WHERE i.id = ? AND i.client_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $invoice_id, $client_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoice = mysqli_fetch_assoc($stmt);

if (!$invoice) {
    die('Invoice not found');
}

// Create PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('CIBIL Repair Services');
$pdf->SetAuthor('CIBIL Repair');
$pdf->SetTitle('GST Invoice ' . $invoice['invoice_no']);
$pdf->SetHeaderData('', 0, '', '');
$pdf->setHeaderFont(array('helvetica', '', 10));
$pdf->setFooterFont(array('helvetica', '', 8));
$pdf->SetDefaultMonospacedFont('courier');
$pdf->SetMargins(15, 15, 15);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(true);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

// Invoice HTML content
$html = '
<style>
    .invoice-box { font-family: helvetica; font-size: 10pt; }
    .header { text-align: center; margin-bottom: 20px; }
    .company-name { font-size: 20pt; font-weight: bold; color: #0d9e78; }
    .gst-tagline { font-size: 9pt; color: #666; }
    .invoice-title { font-size: 16pt; font-weight: bold; text-align: center; margin: 20px 0; }
    .billing-box { border: 1px solid #ddd; padding: 10px; margin: 10px 0; }
    .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    .table th { background-color: #f2f2f2; }
    .text-right { text-align: right; }
    .total-row { font-weight: bold; background-color: #f9f9f9; }
    .footer { margin-top: 30px; font-size: 9pt; text-align: center; color: #666; }
    .gst-details { font-size: 8pt; color: #666; margin-top: 10px; }
</style>

<div class="invoice-box">
    <div class="header">
        <div class="company-name">CIBIL Repair Services</div>
        <div class="gst-tagline">GSTIN: ' . $invoice['company_gstin'] . ' | PAN: ' . $invoice['company_pan'] . '</div>
        <div class="gst-tagline">SAC Code: ' . $invoice['sac_code'] . ' | Services provided under RCM if applicable</div>
    </div>
    
    <div class="invoice-title">TAX INVOICE</div>
    
    <table style="width:100%; margin-bottom:20px;">
        <tr>
            <td style="width:50%;">
                <strong>Invoice No:</strong> ' . $invoice['invoice_no'] . '<br>
                <strong>Invoice Date:</strong> ' . date('d-m-Y', strtotime($invoice['issue_date'])) . '<br>
                <strong>Due Date:</strong> ' . date('d-m-Y', strtotime($invoice['due_date'])) . '
            </td>
            <td style="width:50%;">
                <strong>Place of Supply:</strong> ' . $invoice['billing_state'] . ' (' . $invoice['billing_state_code'] . ')<br>
                <strong>GST Type:</strong> ' . ($invoice['gst_type'] == 'intra_state' ? 'CGST + SGST' : 'IGST') . '
            </td>
        </tr>
    </table>
    
    <div class="billing-box">
        <strong>Billed To:</strong><br>
        ' . $invoice['billing_name'] . '<br>
        ' . nl2br($invoice['billing_address']) . '<br>
        GSTIN: ' . ($invoice['billing_gstin'] ?: 'Not Registered') . '<br>
        PAN: ' . ($invoice['billing_pan'] ?: 'Not Provided') . '<br>
        State: ' . $invoice['billing_state'] . ' (Code: ' . $invoice['billing_state_code'] . ')
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Description of Service</th>
                <th>SAC Code</th>
                <th class="text-right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>' . $invoice['service_name'] . ' (Case No: ' . $invoice['case_no'] . ')</td>
                <td>' . $invoice['sac_code'] . '</td>
                <td class="text-right">' . number_format($invoice['subtotal'], 2) . '</td>
            </tr>
        </tbody>
    </table>
    
    <table style="width:100%; margin-top:10px;">
        <tr>
            <td style="width:70%;"></td>
            <td style="width:30%;">
                <table style="width:100%;">
                    <tr><td>Subtotal:</td><td class="text-right">₹' . number_format($invoice['subtotal'], 2) . '</td></tr>';
                    
if ($invoice['gst_type'] == 'intra_state') {
    $html .= '<tr><td>CGST @ ' . $invoice['cgst_rate'] . '%:</td><td class="text-right">₹' . number_format($invoice['cgst_amount'], 2) . '</td></tr>
              <tr><td>SGST @ ' . $invoice['sgst_rate'] . '%:</td><td class="text-right">₹' . number_format($invoice['sgst_amount'], 2) . '</td></tr>';
} else {
    $html .= '<tr><td>IGST @ ' . $invoice['igst_rate'] . '%:</td><td class="text-right">₹' . number_format($invoice['igst_amount'], 2) . '</td></tr>';
}

$html .= '<tr class="total-row"><td><strong>Total Amount:</strong></td><td class="text-right"><strong>₹' . number_format($invoice['total_amount'], 2) . '</strong></td></tr>
                    <tr><td colspan="2"><small>Amount in words: ' . ucwords(convertNumberToWords($invoice['total_amount'])) . ' Rupees Only</small></td></tr>
                </table>
            </td>
        </tr>
    </table>
    
    <div style="margin-top:20px;">
        <strong>Payment Terms:</strong> ' . ($invoice['payment_terms'] ?: 'Payment is due within 15 days of invoice date.') . '<br>
        <strong>Bank Details:</strong><br>
        Account Name: CIBIL REPAIR SERVICES<br>
        Bank: HDFC Bank<br>
        Account No: XXXXXXXXXXXXXX<br>
        IFSC: HDFC0001234<br>
        GSTIN: ' . $invoice['company_gstin'] . '
    </div>
    
    <div class="gst-details">
        * This is a system generated GST invoice and does not require a physical signature.<br>
        ** Late payment charges @ 18% p.a. will apply for payments received after due date.
    </div>
    
    <div class="footer">
        For CIBIL Repair Services<br>
        Authorised Signatory
    </div>
</div>';

// Helper function to convert number to words
function convertNumberToWords($number) {
    $words = array(
        '0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four',
        '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
        '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen',
        '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen',
        '18' => 'Eighteen', '19' => 'Nineteen', '20' => 'Twenty', '30' => 'Thirty',
        '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy',
        '80' => 'Eighty', '90' => 'Ninety'
    );
    return 'Rupees ' . $words[floor($number)] . ' Only';
}

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('GST_Invoice_' . $invoice['invoice_no'] . '.pdf', 'D');

mysqli_close($conn);
?>