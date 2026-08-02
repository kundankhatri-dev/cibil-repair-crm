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

$alerts = [];

// Check SLA breaches
$sla_breaches = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM operation_cases WHERE status NOT IN ('completed','closed') AND NOW() > sla_due"))['c'] ?? 0;
if ($sla_breaches > 0) {
    $alerts[] = ['title' => 'SLA Breaches Detected', 'message' => "$sla_breaches cases have exceeded their SLA deadline. Requires immediate attention.", 'severity' => 'High', 'date' => date('d M Y')];
}

// Check pending payouts
$pending_payouts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payout_requests WHERE status='pending'"))['total'] ?? 0;
if ($pending_payouts > 50000) {
    $alerts[] = ['title' => 'Large Pending Payouts', 'message' => "₹" . number_format($pending_payouts, 2) . " in partner payouts pending for more than 30 days.", 'severity' => 'Medium', 'date' => date('d M Y')];
}

// Check low conversion rate
$total_leads = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"))['c'] ?? 1;
$converted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM leads WHERE status='converted' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"))['c'] ?? 0;
$conv_rate = round(($converted / $total_leads) * 100);
if ($conv_rate < 20) {
    $alerts[] = ['title' => 'Low Conversion Rate Alert', 'message' => "Conversion rate dropped to $conv_rate% this month. Target is 30%.", 'severity' => 'High', 'date' => date('d M Y')];
}

if (empty($alerts)) {
    $alerts[] = ['title' => 'All Systems Operational', 'message' => 'No critical alerts at this time. Business is running smoothly.', 'severity' => 'Info', 'date' => date('d M Y')];
}

echo json_encode(['success' => true, 'alerts' => $alerts]);
mysqli_close($conn);
?>