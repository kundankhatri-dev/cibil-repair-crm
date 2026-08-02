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

// Client growth trend (last 12 months)
$trend_labels = []; $trend_values = [];
for ($i = 11; $i >= 0; $i--) {
    $trend_labels[] = date('M Y', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='client' AND created_at BETWEEN '$start' AND '$end'"))['c'] ?? 0;
    $trend_values[] = $cnt;
}

// Retention data (last 6 months)
$retention = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M Y', strtotime("-$i months"));
    $start = date('Y-m-01', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $new = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='client' AND created_at BETWEEN '$start' AND '$end'"))['c'] ?? 0;
    
    // Calculate retained (clients who made payment in this month)
    $retained = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT client_id) as c FROM payments WHERE status='paid' AND payment_date BETWEEN '$start' AND '$end'"))['c'] ?? 0;
    $churned = max(0, $new - $retained);
    $rate = $new > 0 ? round(($retained / $new) * 100) : 100;
    
    $retention[] = ['month' => $month, 'new_clients' => $new, 'retained' => $retained, 'churned' => $churned, 'rate' => $rate];
}

echo json_encode(['success' => true, 'trend' => ['labels' => $trend_labels, 'values' => $trend_values], 'retention' => $retention]);
mysqli_close($conn);
?>