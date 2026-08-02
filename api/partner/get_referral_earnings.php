<?php
// ============================================================
// API: Partner Get Referral Earnings - WITH COMMISSION
// ============================================================

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
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

// ========== GET REFERRAL CODE ==========
$referralCode = 'PART-D3D94468';
$result = mysqli_query($conn, "SELECT referral_code FROM partners WHERE user_id = $partner_id");
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    if (!empty($row['referral_code'])) {
        $referralCode = $row['referral_code'];
    }
}

// ========== GET COMMISSION RATE ==========
$commissionRate = 10; // Default
$rateQuery = "SELECT commission_rate FROM partners WHERE user_id = $partner_id";
$rateResult = mysqli_query($conn, $rateQuery);
if ($rateResult) {
    $rateRow = mysqli_fetch_assoc($rateResult);
    if ($rateRow && isset($rateRow['commission_rate'])) {
        $commissionRate = (float)$rateRow['commission_rate'];
    }
}

// ========== GET ALL REFERRALS WITH COMMISSION ==========
$referrals = [];
$query = "SELECT id, referred_name, referred_email, referred_phone, type, 
                 status, commission_earned, commission_rate, registered_at 
          FROM partner_referrals 
          WHERE partner_id = $partner_id 
          ORDER BY registered_at DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $commissionEarned = (float)($row['commission_earned'] ?? 0);
        $rate = (float)($row['commission_rate'] ?? $commissionRate);
        
        $referrals[] = [
            'id' => (int)$row['id'],
            'name' => $row['referred_name'] ?? '—',
            'email' => $row['referred_email'] ?? '—',
            'phone' => $row['referred_phone'] ?? '—',
            'type' => $row['type'] ?? 'partner',
            'status' => $row['status'] ?? 'registered',
            'earnings' => $commissionEarned,
            'commission_rate' => $rate,
            'joined' => $row['registered_at'] ?? ''
        ];
    }
}

// ========== GET STATS ==========
$total_referrals = count($referrals);
$total_earnings = 0;
$converted = 0;

foreach ($referrals as $r) {
    $total_earnings += $r['earnings'];
    if ($r['status'] === 'converted') {
        $converted++;
    }
}

echo json_encode([
    'success' => true,
    'referral_code' => $referralCode,
    'commission_rate' => $commissionRate,
    'earnings' => [
        'total_referrals' => $total_referrals,
        'converted_referrals' => $converted,
        'total_earnings' => $total_earnings,
        'commission_rate' => $commissionRate
    ],
    'referral_earnings' => $referrals
]);

mysqli_close($conn);
?>