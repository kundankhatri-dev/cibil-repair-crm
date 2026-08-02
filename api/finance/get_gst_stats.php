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

$gst_collected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount * 0.18) as total FROM payments WHERE status='paid'"))['total'] ?? 0;
$gst_paid = 0;
$gst_net = $gst_collected - $gst_paid;

$returns = [];
for ($i = 0; $i < 3; $i++) {
    $period = date('F Y', strtotime("-$i months"));
    $taxable = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND MONTH(payment_date) = " . date('n', strtotime("-$i months"))))['total'] ?? 0;
    $returns[] = [
        'period' => $period, 'taxable_value' => (float)$taxable, 'cgst' => (float)$taxable * 0.09,
        'sgst' => (float)$taxable * 0.09, 'igst' => 0, 'total_tax' => (float)$taxable * 0.18,
        'status' => $i == 0 ? 'pending' : 'filed'
    ];
}

echo json_encode(['success' => true, 'gst_collected' => (float)$gst_collected, 'gst_paid' => (float)$gst_paid, 'gst_net' => (float)$gst_net, 'returns' => $returns]);
mysqli_close($conn);
?>