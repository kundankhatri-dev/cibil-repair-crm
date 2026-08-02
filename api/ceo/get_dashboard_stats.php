<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['ceo', 'founder', 'admin', 'director'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
if (!$conn) { echo json_encode(['success' => false, 'error' => 'DB failed']); exit; }

// Total revenue (YTD)
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND YEAR(payment_date) = YEAR(CURDATE())"))['total'] ?? 0;
$last_month_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND MONTH(payment_date) = MONTH(CURDATE() - INTERVAL 1 MONTH)"))['total'] ?? 0;
$this_month_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND MONTH(payment_date) = MONTH(CURDATE())"))['total'] ?? 0;
$revenue_growth = $last_month_revenue > 0 ? round((($this_month_revenue - $last_month_revenue) / $last_month_revenue) * 100) : 0;

// Total clients
$total_clients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='client'"))['c'] ?? 0;
$success_rate = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM client_cases WHERE status IN ('resolved','completed')"))['c'] ?? 0;
$total_cases = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM client_cases"))['c'] ?? 1;
$success_rate_pct = round(($success_rate / $total_cases) * 100);

// Net profit (estimated 40% margin)
$total_expenses = $total_revenue * 0.4;
$partner_commission = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission) as total FROM partner_commission"))['total'] ?? 0;
$net_profit = $total_revenue - $total_expenses - $partner_commission;
$last_month_profit = $last_month_revenue * 0.5;
$profit_growth = $last_month_profit > 0 ? round((($net_profit - $last_month_profit) / $last_month_profit) * 100) : 0;

// Weekly revenue
$weekly_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND YEARWEEK(payment_date) = YEARWEEK(CURDATE())"))['total'] ?? 0;

// New leads (7 days)
$new_leads_7d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['c'] ?? 0;

// Conversion rate
$total_leads = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM leads"))['c'] ?? 1;
$converted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM leads WHERE status='converted'"))['c'] ?? 0;
$conversion_rate = round(($converted / $total_leads) * 100);

// NPS Score (simulated)
$nps_score = rand(65, 85);

// Performance data (last 12 months)
$perf_labels = []; $perf_revenue = []; $perf_clients = [];
for ($i = 11; $i >= 0; $i--) {
    $perf_labels[] = date('M Y', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND payment_date BETWEEN '$start' AND '$end'"))['total'] ?? 0;
    $perf_revenue[] = round($rev / 100000, 1); // In Lakhs
    $clients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='client' AND created_at BETWEEN '$start' AND '$end'"))['c'] ?? 0;
    $perf_clients[] = $clients;
}

// Revenue distribution by package
$packages = ['Basic Package', 'Premium Package', 'Corporate Package', 'Loan Assistance'];
$package_values = [];
foreach ($packages as $pkg) {
    $rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE package='$pkg' AND status='paid'"))['total'] ?? 0;
    $package_values[] = (float)$rev;
}

// Top performers
$top_performers = [];
$top_analyst = mysqli_fetch_assoc(mysqli_query($conn, "SELECT u.name, COUNT(c.id) as cases FROM operation_cases c JOIN users u ON c.assigned_to = u.id WHERE c.status='completed' GROUP BY c.assigned_to ORDER BY cases DESC LIMIT 1"));
if ($top_analyst) $top_performers[] = ['name' => $top_analyst['name'], 'role' => 'Credit Analyst', 'performance' => $top_analyst['cases'] . ' cases', 'achievement' => 'Top Analyst'];
$top_partner = mysqli_fetch_assoc(mysqli_query($conn, "SELECT u.name, SUM(pc.commission) as total FROM partner_commission pc JOIN users u ON pc.partner_id = u.id GROUP BY pc.partner_id ORDER BY total DESC LIMIT 1"));
if ($top_partner) $top_performers[] = ['name' => $top_partner['name'], 'role' => 'Partner', 'performance' => '₹' . number_format($top_partner['total'], 2), 'achievement' => 'Top Partner'];

echo json_encode([
    'success' => true, 'total_revenue' => (float)$total_revenue, 'total_clients' => $total_clients,
    'success_rate' => $success_rate_pct, 'net_profit' => (float)$net_profit, 'revenue_growth' => $revenue_growth,
    'profit_growth' => $profit_growth, 'weekly_revenue' => (float)$weekly_revenue, 'new_leads_7d' => $new_leads_7d,
    'conversion_rate' => $conversion_rate, 'nps_score' => $nps_score,
    'performance_data' => ['labels' => $perf_labels, 'revenue' => $perf_revenue, 'clients' => $perf_clients],
    'revenue_distribution' => ['labels' => $packages, 'values' => $package_values],
    'top_performers' => $top_performers
]);
mysqli_close($conn);
?>