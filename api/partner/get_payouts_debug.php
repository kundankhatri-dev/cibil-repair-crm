<?php
// ============================================================
// API: Get Payouts Debug - CORRECTED
// ============================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// DEBUG: Show table structure
// ============================================================
$debug = [];

// Check table exists
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'partner_payouts'");
$debug['table_exists'] = mysqli_num_rows($checkTable) > 0;

// Get columns
$columns = mysqli_query($conn, "SHOW COLUMNS FROM partner_payouts");
$debug['columns'] = [];
while ($col = mysqli_fetch_assoc($columns)) {
    $debug['columns'][] = $col['Field'];
}

// Get payouts for partner
$query = "SELECT * FROM partner_payouts WHERE partner_id = $partner_id ORDER BY request_date DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    $debug['error'] = mysqli_error($conn);
    $debug['query'] = $query;
    echo json_encode($debug);
    mysqli_close($conn);
    exit;
}

$payouts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $payouts[] = $row;
}

$debug['payouts'] = $payouts;
$debug['total'] = count($payouts);
$debug['partner_id'] = $partner_id;

echo json_encode($debug);

mysqli_close($conn);
?>