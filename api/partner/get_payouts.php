<?php
// ============================================================
// API: Get Payouts - CORRECTED
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
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
// GET PAYOUTS - Using correct column names
// ============================================================
$query = "SELECT 
    id,
    partner_id,
    amount,
    method,
    note as notes,
    status,
    reference,
    request_date,
    paid_date
FROM partner_payouts 
WHERE partner_id = $partner_id 
ORDER BY request_date DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        'success' => false, 
        'error' => 'Query failed: ' . mysqli_error($conn)
    ]);
    mysqli_close($conn);
    exit;
}

$payouts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $payouts[] = [
        'id' => (int)$row['id'],
        'amount' => (float)$row['amount'],
        'method' => $row['method'] ?? 'bank_transfer',
        'status' => $row['status'] ?? 'pending',
        'reference' => $row['reference'] ?? '—',
        'notes' => $row['notes'] ?? '',
        'request_date' => $row['request_date'] ? date('Y-m-d', strtotime($row['request_date'])) : '',
        'paid_date' => $row['paid_date'] ? date('Y-m-d', strtotime($row['paid_date'])) : '',
        'created_at' => $row['request_date']
    ];
}

echo json_encode([
    'success' => true,
    'payouts' => $payouts,
    'total' => count($payouts)
]);

mysqli_close($conn);
?>