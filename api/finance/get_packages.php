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

$packages = [
    ['name' => 'Basic Package', 'price' => 4999],
    ['name' => 'Premium Package', 'price' => 9999],
    ['name' => 'Corporate Package', 'price' => 19999],
    ['name' => 'Loan Assistance Package', 'price' => 2999]
];

$basic_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE package='Basic Package' AND status='paid'"))['c'] ?? 0;
$premium_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE package='Premium Package' AND status='paid'"))['c'] ?? 0;
$corporate_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE package='Corporate Package' AND status='paid'"))['c'] ?? 0;
$loan_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE package='Loan Assistance Package' AND status='paid'"))['c'] ?? 0;

$package_data = [];
foreach ($packages as $pkg) {
    $sales = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE package='{$pkg['name']}' AND status='paid'"))['c'] ?? 0;
    $revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE package='{$pkg['name']}' AND status='paid'"))['total'] ?? 0;
    $active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT client_id) as c FROM payments WHERE package='{$pkg['name']}' AND status='paid'"))['c'] ?? 0;
    $package_data[] = ['name' => $pkg['name'], 'price' => $pkg['price'], 'sales' => $sales, 'revenue' => (float)$revenue, 'active_clients' => $active];
}

echo json_encode([
    'success' => true, 'basic_count' => $basic_count, 'premium_count' => $premium_count,
    'corporate_count' => $corporate_count, 'loan_count' => $loan_count, 'packages' => $package_data
]);
mysqli_close($conn);
?>