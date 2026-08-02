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

$this_month = date('Y-m');
$last_month = date('Y-m', strtotime('-1 month'));

$metrics = [
    ['metric' => 'Total Revenue', 'this_month' => 0, 'last_month' => 0, 'ytd' => 0],
    ['metric' => 'Partner Commission', 'this_month' => 0, 'last_month' => 0, 'ytd' => 0],
    ['metric' => 'Employee Incentives', 'this_month' => 0, 'last_month' => 0, 'ytd' => 0],
    ['metric' => 'Operating Expenses', 'this_month' => 0, 'last_month' => 0, 'ytd' => 0],
    ['metric' => 'Net Profit', 'this_month' => 0, 'last_month' => 0, 'ytd' => 0]
];

// This month revenue
$tm_rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND DATE_FORMAT(payment_date, '%Y-%m') = '$this_month'"))['total'] ?? 0;
$lm_rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND DATE_FORMAT(payment_date, '%Y-%m') = '$last_month'"))['total'] ?? 0;
$ytd_rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid' AND YEAR(payment_date) = YEAR(CURDATE())"))['total'] ?? 0;
$metrics[0] = ['metric' => 'Total Revenue', 'this_month' => '₹' . number_format($tm_rev, 2), 'last_month' => '₹' . number_format($lm_rev, 2), 'change' => $lm_rev > 0 ? round((($tm_rev - $lm_rev) / $lm_rev) * 100) : 0, 'ytd' => '₹' . number_format($ytd_rev, 2)];

// Partner commission
$tm_comm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission) as total FROM partner_commission WHERE DATE_FORMAT(created_at, '%Y-%m') = '$this_month'"))['total'] ?? 0;
$lm_comm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission) as total FROM partner_commission WHERE DATE_FORMAT(created_at, '%Y-%m') = '$last_month'"))['total'] ?? 0;
$ytd_comm = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(commission) as total FROM partner_commission WHERE YEAR(created_at) = YEAR(CURDATE())"))['total'] ?? 0;
$metrics[1] = ['metric' => 'Partner Commission', 'this_month' => '₹' . number_format($tm_comm, 2), 'last_month' => '₹' . number_format($lm_comm, 2), 'change' => $lm_comm > 0 ? round((($tm_comm - $lm_comm) / $lm_comm) * 100) : 0, 'ytd' => '₹' . number_format($ytd_comm, 2)];

$metrics[2] = ['metric' => 'Employee Incentives', 'this_month' => '₹0', 'last_month' => '₹0', 'change' => 0, 'ytd' => '₹0'];
$metrics[3] = ['metric' => 'Operating Expenses', 'this_month' => '₹' . number_format($tm_rev * 0.4, 2), 'last_month' => '₹' . number_format($lm_rev * 0.4, 2), 'change' => 0, 'ytd' => '₹' . number_format($ytd_rev * 0.4, 2)];
$metrics[4] = ['metric' => 'Net Profit', 'this_month' => '₹' . number_format($tm_rev * 0.6 - $tm_comm, 2), 'last_month' => '₹' . number_format($lm_rev * 0.6 - $lm_comm, 2), 'change' => 0, 'ytd' => '₹' . number_format($ytd_rev * 0.6 - $ytd_comm, 2)];

echo json_encode(['success' => true, 'metrics' => $metrics]);
mysqli_close($conn);
?>