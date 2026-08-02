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

// YoY Data (last 4 years)
$yoy_labels = ['2022', '2023', '2024', '2025'];
$yoy_values = [];
foreach ($yoy_labels as $year) {
    $rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND YEAR(payment_date) = $year"))['total'] ?? 0;
    $yoy_values[] = round($rev / 100000, 1);
}

// Service line revenue
$services = ['Written Off Clearance', 'Settled Clearance', 'Credit Report Analysis', 'Profile Correction', 'Loan Assistance'];
$service_values = [];
foreach ($services as $service) {
    $rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE service='$service' AND status='paid'"))['total'] ?? 0;
    $service_values[] = (float)$rev;
}

echo json_encode(['success' => true, 'yoy_data' => ['labels' => $yoy_labels, 'values' => $yoy_values], 'service_revenue' => ['labels' => $services, 'values' => $service_values]]);
mysqli_close($conn);
?>