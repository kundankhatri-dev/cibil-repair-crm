<?php
// ============================================================
// API: Partner Get Referral Stats - SIMPLE VERSION
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

// ========== GET STATS ==========
$signups = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE referral_code = '$referralCode'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $signups = (int)($row['total'] ?? 0);
}

$conversions = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM leads l 
          JOIN users u ON l.partner_id = u.id 
          WHERE u.referral_code = '$referralCode' AND l.status = 'converted'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $conversions = (int)($row['total'] ?? 0);
}

$earnings = 0;
$result = mysqli_query($conn, "SELECT SUM(l.amount) as total FROM leads l 
          JOIN users u ON l.partner_id = u.id 
          WHERE u.referral_code = '$referralCode' AND l.status = 'converted'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $earnings = (float)($row['total'] ?? 0);
}

// ========== GET REFERRAL LIST ==========
$referrals = [];
$query = "SELECT id, referred_name, referred_email, referred_phone, type, 
                 status, commission_earned, registered_at, notes
          FROM partner_referrals 
          WHERE partner_id = $partner_id 
          ORDER BY registered_at DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $referrals[] = [
            'id' => (int)$row['id'],
            'name' => $row['referred_name'] ?? '—',
            'email' => $row['referred_email'] ?? '—',
            'phone' => $row['referred_phone'] ?? '—',
            'type' => $row['type'] ?? 'partner',
            'status' => $row['status'] ?? 'registered',
            'earnings' => (float)($row['commission_earned'] ?? 0),
            'joined' => $row['registered_at'] ?? '',
            'notes' => $row['notes'] ?? ''
        ];
    }
}

echo json_encode([
    'success' => true,
    'referral_code' => $referralCode,
    'total_signups' => $signups,
    'conversions' => $conversions,
    'earnings' => $earnings,
    'rank' => 0,
    'referrals' => $referrals
]);

mysqli_close($conn);
?>