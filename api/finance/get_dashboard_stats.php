<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://cibilrepair.in');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

$allowed_roles = ['finance_team', 'admin', 'manager', 'super_admin'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get total revenue from payments table
$total_revenue = 0;
$result = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status='paid'");
if ($result) $total_revenue = mysqli_fetch_assoc($result)['total'];

// Get total invoices
$total_invoices = 0;
$result = mysqli_query($conn, "SELECT COALESCE(COUNT(*), 0) as c FROM invoices");
if ($result) $total_invoices = mysqli_fetch_assoc($result)['c'];

// Get partner commission due
$partner_commission_due = 0;
$result = mysqli_query($conn, "SELECT COALESCE(SUM(commission_amount), 0) as total FROM partner_commission WHERE status='earned'");
if ($result) $partner_commission_due = mysqli_fetch_assoc($result)['total'];

// Get pending payouts
$pending_payouts = 0;
$result = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM payout_requests WHERE status='pending'");
if ($result) $pending_payouts = mysqli_fetch_assoc($result)['total'];

// Revenue change calculation
$last_month = 0;
$result = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status='paid' AND MONTH(date) = MONTH(CURDATE() - INTERVAL 1 MONTH)");
if ($result) $last_month = mysqli_fetch_assoc($result)['total'];

$this_month = 0;
$result = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status='paid' AND MONTH(date) = MONTH(CURDATE())");
if ($result) $this_month = mysqli_fetch_assoc($result)['total'];

$revenue_change = $last_month > 0 ? round((($this_month - $last_month) / $last_month) * 100) : 0;

$new_invoices = 0;
$result = mysqli_query($conn, "SELECT COALESCE(COUNT(*), 0) as c FROM invoices WHERE MONTH(invoice_date) = MONTH(CURDATE())");
if ($result) $new_invoices = mysqli_fetch_assoc($result)['c'];

// Revenue trend (last 6 months)
$trend_labels = [];
$trend_values = [];
for ($i = 5; $i >= 0; $i--) {
    $trend_labels[] = date('M', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $rev = 0;
    $result = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status='paid' AND date BETWEEN '$start' AND '$end'");
    if ($result) $rev = mysqli_fetch_assoc($result)['total'];
    $trend_values[] = (float)$rev;
}

// Package revenue
$packages = ['Basic Package', 'Premium Package', 'Corporate Package', 'Loan Assistance Package'];
$package_values = [];
foreach ($packages as $pkg) {
    $rev = 0;
    $result = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE package='$pkg' AND status='paid'");
    if ($result) $rev = mysqli_fetch_assoc($result)['total'];
    $package_values[] = (float)$rev;
}

// Recent payments - FIXED: removed created_at reference
$recent_payments = [];
$recent = mysqli_query($conn, "SELECT p.*, p.clientName as client_name FROM payments p ORDER BY p.id DESC LIMIT 10");
if ($recent) {
    while ($row = mysqli_fetch_assoc($recent)) {
        $row['date_display'] = date('d M Y', strtotime($row['date'] ?? date('Y-m-d')));
        $recent_payments[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'total_revenue' => (float)$total_revenue,
    'total_invoices' => (int)$total_invoices,
    'partner_commission_due' => (float)$partner_commission_due,
    'pending_payouts' => (float)$pending_payouts,
    'revenue_change' => $revenue_change,
    'new_invoices' => $new_invoices,
    'revenue_trend' => ['labels' => $trend_labels, 'values' => $trend_values],
    'package_revenue' => ['labels' => $packages, 'values' => $package_values],
    'recent_payments' => $recent_payments
]);

mysqli_close($conn);
?>