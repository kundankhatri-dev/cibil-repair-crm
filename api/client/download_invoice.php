<?php
// api/client/download_invoice.php - Download GST Invoice as PDF
session_start();

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    die('Database connection failed');
}

// Get client_id
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';

if (!$client_id) {
    die('Not authenticated');
}

// Get invoice ID
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf'; // pdf or html

if ($invoice_id <= 0) {
    die('Invalid invoice ID');
}

// ========== FETCH INVOICE DETAILS ==========
$query = "SELECT 
            i.*,
            u.name as client_name,
            u.email as client_email,
            u.phone as client_phone
          FROM gst_invoices i
          JOIN users u ON i.client_id = u.id
          WHERE i.id = ? AND i.client_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $invoice_id, $client_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoice = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$invoice) {
    die('Invoice not found or access denied');
}

// ========== HELPER FUNCTION: NUMBER TO WORDS ==========
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
    
    if ($number < 20) {
        return $words[$number];
    }
    
    $tens = floor($number / 10) * 10;
    $units = $number % 10;
    
    if ($units == 0) {
        return $words[$tens];
    }
    
    return $words[$tens] . ' ' . $words[$units];
}

function getAmountInWords($amount) {
    $rupees = floor($amount);
    $paise = round(($amount - $rupees) * 100);
    
    if ($rupees == 0) {
        return 'Zero Rupees Only';
    }
    
    // For simplicity, convert rupees only
    if ($rupees < 100000) {
        $words = convertNumberToWords($rupees);
    } else {
        $lakhs = floor($rupees / 100000);
        $thousands = floor(($rupees % 100000) / 1000);
        $remainder = $rupees % 1000;
        
        $words = '';
        if ($lakhs > 0) {
            $words .= convertNumberToWords($lakhs) . ' Lakh ';
        }
        if ($thousands > 0) {
            $words .= convertNumberToWords($thousands) . ' Thousand ';
        }
        if ($remainder > 0) {
            $words .= convertNumberToWords($remainder);
        }
    }
    
    return ucfirst(trim($words)) . ' Rupees Only';
}

// ========== GENERATE HTML INVOICE ==========
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>GST Invoice ' . $invoice['invoice_no'] . '</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "DejaVu Sans", "Helvetica Neue", Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
            background: #fff;
            padding: 20px;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .invoice-header {
            background: linear-gradient(135deg, #0b2a23, #0e3d30);
            color: #fff;
            padding: 25px 30px;
            text-align: center;
        }
        .company-name {
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .company-tagline {
            font-size: 9pt;
            opacity: 0.8;
            margin-top: 5px;
        }
        .gst-details {
            font-size: 8pt;
            opacity: 0.7;
            margin-top: 8px;
        }
        .invoice-title {
            background: #f3f4f6;
            padding: 12px;
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            color: #0d9e78;
            border-bottom: 2px solid #0d9e78;
        }
        .invoice-info {
            padding: 20px 30px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
        }
        .info-box {
            flex: 1;
        }
        .info-label {
            font-size: 9pt;
            font-weight: bold;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 10pt;
            color: #111827;
        }
        .billing-section {
            padding: 20px 30px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 30px;
        }
        .billing-box {
            flex: 1;
        }
        .billing-title {
            font-size: 10pt;
            font-weight: bold;
            color: #0d9e78;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .items-table th {
            background: #f3f4f6;
            padding: 10px 12px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
        }
        .text-right {
            text-align: right;
        }
        .totals-section {
            padding: 20px 30px;
            display: flex;
            justify-content: flex-end;
        }
        .totals-table {
            width: 300px;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 0;
            font-size: 9pt;
        }
        .totals-table .total-row {
            font-weight: bold;
            font-size: 11pt;
            border-top: 2px solid #e5e7eb;
            margin-top: 5px;
            padding-top: 8px;
        }
        .amount-words {
            padding: 10px 30px 20px;
            font-size: 9pt;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .bank-details {
            padding: 15px 30px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            font-size: 8pt;
            color: #6b7280;
        }
        .footer {
            padding: 15px 30px;
            text-align: center;
            font-size: 8pt;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
        }
        .signature {
            padding: 20px 30px;
            text-align: right;
            font-size: 9pt;
            border-top: 1px solid #e5e7eb;
        }
        hr {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="company-name">CIBIL REPAIR SERVICES</div>
            <div class="company-tagline">Credit Repair & CIBIL Score Improvement</div>
            <div class="gst-details">
                GSTIN: ' . $invoice['company_gstin'] . ' | PAN: ' . $invoice['company_pan'] . ' | SAC: ' . $invoice['sac_code'] . '
            </div>
        </div>
        
        <div class="invoice-title">TAX INVOICE</div>
        
        <div class="invoice-info">
            <div class="info-box">
                <div class="info-label">Invoice No.</div>
                <div class="info-value"><strong>' . $invoice['invoice_no'] . '</strong></div>
            </div>
            <div class="info-box">
                <div class="info-label">Invoice Date</div>
                <div class="info-value">' . date('d-m-Y', strtotime($invoice['issue_date'])) . '</div>
            </div>
            <div class="info-box">
                <div class="info-label">Due Date</div>
                <div class="info-value">' . date('d-m-Y', strtotime($invoice['due_date'])) . '</div>
            </div>
            <div class="info-box">
                <div class="info-label">Place of Supply</div>
                <div class="info-value">' . ($invoice['billing_state'] ?? 'Karnataka') . ' (Code: ' . ($invoice['billing_state_code'] ?? '29') . ')</div>
            </div>
        </div>
        
        <div class="billing-section">
            <div class="billing-box">
                <div class="billing-title">BILLED TO</div>
                <div><strong>' . ($invoice['billing_name'] ?? $invoice['client_name']) . '</strong></div>
                <div>' . nl2br($invoice['billing_address'] ?? '') . '</div>
                <div>GSTIN: ' . ($invoice['billing_gstin'] ?: 'Not Registered') . '</div>
                <div>PAN: ' . ($invoice['billing_pan'] ?: 'Not Provided') . '</div>
                <div>Email: ' . $invoice['client_email'] . '</div>
                <div>Phone: ' . $invoice['client_phone'] . '</div>
            </div>
            <div class="billing-box">
                <div class="billing-title">SHIPPED TO</div>
                <div><strong>' . ($invoice['billing_name'] ?? $invoice['client_name']) . '</strong></div>
                <div>' . nl2br($invoice['billing_address'] ?? '') . '</div>
                <div>' . ($invoice['billing_state'] ?? '') . ' - ' . ($invoice['pincode'] ?? '') . '</div>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="45%">Description of Service</th>
                    <th width="15%">SAC Code</th>
                    <th width="15%" class="text-right">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>' . $invoice['service_name'] . ($invoice['case_no'] ? '<br><small>Case No: ' . $invoice['case_no'] . '</small>' : '') . '</td>
                    <td>' . $invoice['sac_code'] . '</td>
                    <td class="text-right">' . number_format($invoice['subtotal'], 2) . '</td>
                </tr>
            </tbody>
        </table>
        
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td width="70%">Subtotal</td>
                    <td class="text-right">₹ ' . number_format($invoice['subtotal'], 2) . '</td>
                </tr>';
                
if ($invoice['gst_type'] == 'intra_state') {
    $html .= '<tr>
                    <td>CGST @ ' . $invoice['cgst_rate'] . '%</td>
                    <td class="text-right">₹ ' . number_format($invoice['cgst_amount'], 2) . '</td>
                </tr>
                <tr>
                    <td>SGST @ ' . $invoice['sgst_rate'] . '%</td>
                    <td class="text-right">₹ ' . number_format($invoice['sgst_amount'], 2) . '</td>
                </tr>';
} else {
    $html .= '<tr>
                    <td>IGST @ ' . $invoice['igst_rate'] . '%</td>
                    <td class="text-right">₹ ' . number_format($invoice['igst_amount'], 2) . '</td>
                </tr>';
}

$html .= '<tr class="total-row">
                    <td><strong>Total Amount</strong></td>
                    <td class="text-right"><strong>₹ ' . number_format($invoice['total_amount'], 2) . '</strong></td>
                </tr>
            </table>
        </div>
        
        <div class="amount-words">
            <strong>Amount in words:</strong> ' . getAmountInWords($invoice['total_amount']) . '
        </div>
        
        <div class="bank-details">
            <strong>Bank Details for Payment:</strong><br>
            Account Name: CIBIL REPAIR SERVICES<br>
            Bank: HDFC Bank Ltd.<br>
            Account No: 502000XXXXXXXXXX<br>
            IFSC Code: HDFC0001234<br>
            UPI ID: cibilrepair@hdfcbank<br>
            GSTIN: ' . $invoice['company_gstin'] . '
        </div>
        
        <div class="footer">
            * This is a system generated GST invoice and does not require a physical signature.<br>
            ** Late payment charges @ 18% p.a. will apply for payments received after due date.<br>
            *** Subject to ' . ($invoice['billing_state'] ?? 'Bengaluru') . ' jurisdiction
        </div>
        
        <div class="signature">
            For CIBIL REPAIR SERVICES<br><br><br>
            <strong>(Authorised Signatory)</strong>
        </div>
    </div>
</body>
</html>';

// ========== OUTPUT INVOICE ==========
if ($format === 'html') {
    // Output as HTML
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
} else {
    // Output as PDF using TCPDF
    require_once('../tcpdf/tcpdf.php');
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('CIBIL Repair Services');
    $pdf->SetAuthor('CIBIL Repair');
    $pdf->SetTitle('GST Invoice ' . $invoice['invoice_no']);
    $pdf->SetSubject('Invoice for Credit Repair Services');
    $pdf->SetKeywords('GST, Invoice, CIBIL, Repair');
    
    $pdf->SetHeaderData('', 0, '', '');
    $pdf->setHeaderFont(array('helvetica', '', 10));
    $pdf->setFooterFont(array('helvetica', '', 8));
    $pdf->SetDefaultMonospacedFont('courier');
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(true);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $pdf->Output('GST_Invoice_' . $invoice['invoice_no'] . '.pdf', 'D');
}

mysqli_close($conn);
?>