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

// Total revenue
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid'"))['total'] ?? 0;
$monthly_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND MONTH(payment_date) = MONTH(CURDATE())"))['total'] ?? 0;
$last_month_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND MONTH(payment_date) = MONTH(CURDATE() - INTERVAL 1 MONTH)"))['total'] ?? 0;
$revenue_growth = $last_month_revenue > 0 ? round((($monthly_revenue - $last_month_revenue) / $last_month_revenue) * 100) : 0;

// Clients
$total_clients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='client'"))['c'] ?? 0;
$new_clients_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='client' AND MONTH(created_at) = MONTH(CURDATE())"))['c'] ?? 0;
$last_month_clients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='client' AND MONTH(created_at) = MONTH(CURDATE() - INTERVAL 1 MONTH)"))['c'] ?? 0;
$client_growth = $last_month_clients > 0 ? round((($new_clients_month - $last_month_clients) / $last_month_clients) * 100) : 0;

// CIBIL Score
$avg_cibil = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(cibil_score) as avg FROM credit_analysis WHERE cibil_score IS NOT NULL"))['avg'] ?? 0;

// Partners
$active_partners = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='partner' AND status='active'"))['c'] ?? 0;

// Employees
$total_employees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role IN ('employee','credit_analyst','support_team','operations_team')"))['c'] ?? 0;

// Case success rate
$total_cases = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM client_cases"))['c'] ?? 1;
$resolved_cases = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM client_cases WHERE status IN ('resolved','completed')"))['c'] ?? 0;
$case_success_rate = round(($resolved_cases / $total_cases) * 100);

// Avg rating
$avg_rating = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) as avg FROM support_tickets WHERE rating IS NOT NULL"))['avg'] ?? 4.9;

// Revenue trend (last 6 months)
$trend_labels = []; $trend_values = [];
for ($i = 5; $i >= 0; $i--) {
    $trend_labels[] = date('M', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND payment_date BETWEEN '$start' AND '$end'"))['total'] ?? 0;
    $trend_values[] = (float)$rev;
}

// Business breakdown
$packages = ['Basic Package', 'Premium Package', 'Corporate Package', 'Loan Assistance'];
$package_values = [];
foreach ($packages as $pkg) {
    $rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE package='$pkg' AND status='paid'"))['total'] ?? 0;
    $package_values[] = (float)$rev;
}

// Highlights
$highlights = [];
$top_partner = mysqli_fetch_assoc(mysqli_query($conn, "SELECT u.name, SUM(pc.commission) as total FROM partner_commission pc JOIN users u ON pc.partner_id = u.id GROUP BY pc.partner_id ORDER BY total DESC LIMIT 1"));
if ($top_partner) $highlights[] = "🏆 Top Performing Partner: {$top_partner['name']} with ₹" . number_format($top_partner['total'], 2) . " commission";

$best_performer = mysqli_fetch_assoc(mysqli_query($conn, "SELECT u.name, COUNT(c.id) as cases FROM operation_cases c JOIN users u ON c.assigned_to = u.id WHERE c.status='completed' GROUP BY c.assigned_to ORDER BY cases DESC LIMIT 1"));
if ($best_performer) $highlights[] = "⭐ Best Employee: {$best_performer['name']} with {$best_performer['cases']} cases completed this month";

$high_revenue_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT DATE_FORMAT(payment_date, '%M %Y') as month, SUM(amount) as total FROM payments WHERE status='paid' GROUP BY MONTH(payment_date) ORDER BY total DESC LIMIT 1"));
if ($high_revenue_month) $highlights[] = "💰 Highest Revenue Month: {$high_revenue_month['month']} - ₹" . number_format($high_revenue_month['total'], 2);

echo json_encode([
    'success' => true, 'total_revenue' => (float)$total_revenue, 'monthly_revenue' => (float)$monthly_revenue,
    'revenue_growth' => $revenue_growth, 'total_clients' => $total_clients, 'new_clients_month' => $new_clients_month,
    'client_growth' => $client_growth, 'avg_cibil_score' => round($avg_cibil), 'active_partners' => $active_partners,
    'total_employees' => $total_employees, 'case_success_rate' => $case_success_rate, 'avg_rating' => round($avg_rating, 1),
    'revenue_trend' => ['labels' => $trend_labels, 'values' => $trend_values],
    'business_breakdown' => ['labels' => $packages, 'values' => $package_values],
    'highlights' => $highlights
]);
mysqli_close($conn);
?>