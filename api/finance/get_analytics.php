<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['finance_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

// Financial summary (last 6 months)
$labels = []; $revenue = []; $expenses = [];
for ($i = 5; $i >= 0; $i--) {
    $labels[] = date('M', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND payment_date BETWEEN '$start' AND '$end'"))['total'] ?? 0;
    $revenue[] = (float)$rev;
    $exp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission) as total FROM partner_commission WHERE status='paid' AND paid_date BETWEEN '$start' AND '$end'"))['total'] ?? 0;
    $expenses[] = (float)$exp;
}

// Top partners
$top_partners = mysqli_query($conn, "SELECT p.name, SUM(pc.commission) as total FROM partner_commission pc JOIN users p ON pc.partner_id = p.id GROUP BY pc.partner_id ORDER BY total DESC LIMIT 5");
$partner_labels = []; $partner_values = [];
while ($row = mysqli_fetch_assoc($top_partners)) {
    $partner_labels[] = $row['name'];
    $partner_values[] = (float)$row['total'];
}

echo json_encode([
    'success' => true,
    'financial_summary' => ['labels' => $labels, 'revenue' => $revenue, 'expenses' => $expenses],
    'top_partners' => ['labels' => $partner_labels, 'values' => $partner_values]
]);
mysqli_close($conn);
?>