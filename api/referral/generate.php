<?php
require_once '../config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Generate unique referral code
$referral_code = 'REF' . strtoupper(substr(md5($user_id . time()), 0, 8));

// Check if user already has a referral code
$check = mysqli_query($conn, "SELECT referral_code FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($check);

if (!$user['referral_code']) {
    mysqli_query($conn, "UPDATE users SET referral_code = '$referral_code' WHERE id = $user_id");
} else {
    $referral_code = $user['referral_code'];
}

$referral_link = "https://cibilrepair.in/register.html?ref=" . $referral_code;

// Get referral stats
$stats_query = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted, SUM(commission_earned) as total_commission FROM referrals WHERE referrer_id = $user_id";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent referrals
$recent_query = "SELECT referred_name, referred_email, status, commission_earned, created_at FROM referrals WHERE referrer_id = $user_id ORDER BY created_at DESC LIMIT 10";
$recent_result = mysqli_query($conn, $recent_query);
$recent = mysqli_fetch_all($recent_result, MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'referral_code' => $referral_code,
    'referral_link' => $referral_link,
    'stats' => [
        'total_referrals' => (int)$stats['total'],
        'converted_referrals' => (int)$stats['converted'],
        'total_commission' => (float)$stats['total_commission']
    ],
    'recent_referrals' => $recent
]);

mysqli_close($conn);
?>