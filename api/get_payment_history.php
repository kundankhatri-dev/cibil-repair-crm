<?php
// ============================================================
// CIBIL REPAIR CRM - Get Payment History API
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get all payments for this user
$query = "SELECT * FROM payments WHERE user_id = $user_id ORDER BY id DESC";
$result = mysqli_query($conn, $query);

$payments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $payments[] = [
        'id' => intval($row['id']),
        'clientName' => $row['clientName'] ?? '',
        'amount' => floatval($row['amount'] ?? 0),
        'service' => $row['service'] ?? '',
        'status' => $row['status'] ?? 'pending',
        'date' => $row['date'] ?? null,
        'transaction_id' => $row['transaction_id'] ?? '',
        'payment_mode' => $row['payment_mode'] ?? '',
        'package' => $row['package'] ?? '',
        'user_id' => intval($row['user_id'] ?? 0)
    ];
}

// Get status counts
$statusCounts = [];
$statuses = ['pending', 'completed', 'success', 'paid', 'failed', 'refunded', 'cancelled'];
foreach ($statuses as $s) {
    $sResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM payments WHERE user_id = $user_id AND status = '$s'");
    $sRow = mysqli_fetch_assoc($sResult);
    $statusCounts[$s] = $sRow ? intval($sRow['count']) : 0;
}
$statusCounts['total'] = count($payments);

// Get total amount
$totalAmount = 0;
foreach ($payments as $p) {
    if (in_array($p['status'], ['completed', 'success', 'paid'])) {
        $totalAmount += $p['amount'];
    }
}

echo json_encode([
    'success' => true,
    'data' => [
        'payments' => $payments,
        'total' => count($payments),
        'status_counts' => $statusCounts,
        'total_amount' => $totalAmount,
        'user_id' => $user_id,
        'generated_at' => date('Y-m-d H:i:s')
    ]
]);

mysqli_close($conn);
?>