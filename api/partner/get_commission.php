<?php
// ============================================================
// API: Partner Get Commission - MINIMAL WORKING VERSION
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];

// ========== DATABASE CONNECTION ==========
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ========== SIMPLE QUERY ==========
$query = "SELECT id, name, amount, created_at 
          FROM leads 
          WHERE partner_id = $partner_id AND status = 'converted' AND amount > 0
          ORDER BY created_at DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Query failed: ' . mysqli_error($conn)]);
    mysqli_close($conn);
    exit;
}

$commissions = [];
$totalCommission = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $amount = (float)($row['amount'] ?? 0);
    $commissionAmount = $amount * 0.30;
    $totalCommission += $commissionAmount;
    
    $commissions[] = [
        'id' => (int)$row['id'],
        'customer_name' => $row['name'] ?? '—',
        'service_type' => '—',
        'service_amount' => $amount,
        'commission_amount' => $commissionAmount,
        'commission_rate' => 30,
        'status' => 'earned',
        'created_at' => $row['created_at'] ?? ''
    ];
}

echo json_encode([
    'success' => true,
    'data' => $commissions,
    'total' => count($commissions),
    'summary' => [
        'total_commission' => round($totalCommission, 2),
        'pending_commission' => round($totalCommission, 2),
        'paid_commission' => 0,
        'average_commission' => count($commissions) > 0 ? round($totalCommission / count($commissions), 2) : 0
    ]
]);

mysqli_close($conn);
?>