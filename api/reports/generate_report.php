<?php
// api/reports/generate_report.php
session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized. Please login first.');
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'leads';
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Clear any output buffers
while (ob_get_level()) ob_end_clean();

require_once 'fpdf/fpdf.php';

// Database connection
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    die('Database connection failed');
}

// Validate dates
$from_date = date('Y-m-d', strtotime($from_date));
$to_date = date('Y-m-d', strtotime($to_date));

// Get data based on report type
if ($type == 'leads') {
    $query = "SELECT id, customer_name, customer_phone, COALESCE(service_type, service) as service, status, 
              DATE_FORMAT(created_at, '%d-%m-%Y') as date, COALESCE(commission_amount, 0) as commission 
              FROM partner_leads 
              WHERE partner_id = $user_id AND DATE(created_at) BETWEEN '$from_date' AND '$to_date' 
              ORDER BY created_at DESC";
    $headers = ['ID', 'Customer Name', 'Phone', 'Service', 'Status', 'Date', 'Commission'];
    $title = 'Leads Report';
} else {
    $query = "SELECT id, customer_name, customer_phone, COALESCE(service_type, service) as service, status, 
              DATE_FORMAT(created_at, '%d-%m-%Y') as date, COALESCE(commission_amount, 0) as commission 
              FROM partner_leads 
              WHERE partner_id = $user_id AND DATE(created_at) BETWEEN '$from_date' AND '$to_date' 
              ORDER BY created_at DESC";
    $headers = ['ID', 'Customer Name', 'Phone', 'Service', 'Status', 'Date', 'Commission'];
    $title = 'Leads Report';
}

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calculate total commission
$total_commission = 0;
foreach ($data as $row) {
    $total_commission += $row['commission'];
}

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Title
$pdf->SetTextColor(31, 138, 114);
$pdf->Cell(190, 10, 'CIBIL Repair - ' . $title, 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(190, 6, 'Period: ' . $from_date . ' to ' . $to_date, 0, 1, 'C');
$pdf->Cell(190, 6, 'Generated: ' . date('d-m-Y H:i:s'), 0, 1, 'C');
$pdf->Cell(190, 6, 'Total Records: ' . count($data), 0, 1, 'C');
$pdf->Cell(190, 6, 'Total Commission: ₹' . number_format($total_commission, 2), 0, 1, 'C');
$pdf->Ln(10);

// Table Header
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(31, 138, 114);
$pdf->SetTextColor(255, 255, 255);

$col_width = 190 / count($headers);
foreach ($headers as $header) {
    $pdf->Cell($col_width, 8, $header, 1, 0, 'C', true);
}
$pdf->Ln();

// Table Data
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 0, 0);
$fill = false;

foreach ($data as $row) {
    $pdf->Cell($col_width, 6, $row['id'], 1, 0, 'L', $fill);
    $pdf->Cell($col_width, 6, substr($row['customer_name'], 0, 25), 1, 0, 'L', $fill);
    $pdf->Cell($col_width, 6, $row['customer_phone'], 1, 0, 'L', $fill);
    $pdf->Cell($col_width, 6, substr($row['service'], 0, 20), 1, 0, 'L', $fill);
    $pdf->Cell($col_width, 6, $row['status'], 1, 0, 'L', $fill);
    $pdf->Cell($col_width, 6, $row['date'], 1, 0, 'L', $fill);
    $pdf->Cell($col_width, 6, '₹' . number_format($row['commission'], 2), 1, 0, 'L', $fill);
    $pdf->Ln();
    $fill = !$fill;
}

// Output PDF
$pdf->Output('D', $title . '_' . date('Y-m-d') . '.pdf');

mysqli_close($conn);
?>