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

// Revenue breakdown by source
$sources = ['Direct', 'Partner Referral', 'Website', 'WhatsApp', 'Other'];
$source_values = [];
foreach ($sources as $src) {
    $val = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND source='$src'"))['total'] ?? 0;
    $source_values[] = (float)$val;
}

// Monthly revenue (last 12 months)
$monthly_labels = []; $monthly_values = [];
for ($i = 11; $i >= 0; $i--) {
    $monthly_labels[] = date('M Y', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND payment_date BETWEEN '$start' AND '$end'"))['total'] ?? 0;
    $monthly_values[] = (float)$rev;
}

echo json_encode([
    'success' => true,
    'breakdown' => ['labels' => $sources, 'values' => $source_values],
    'monthly' => ['labels' => $monthly_labels, 'values' => $monthly_values]
]);
mysqli_close($conn);
?>