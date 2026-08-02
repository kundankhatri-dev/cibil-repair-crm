<?php
// api/partner/get_tier.php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Get partner tier details
$query = "SELECT t.*, 
          CASE 
              WHEN t.tier_level = 1 THEN '🥉 Bronze'
              WHEN t.tier_level = 2 THEN '🥈 Silver'
              WHEN t.tier_level = 3 THEN '🥇 Gold'
              WHEN t.tier_level = 4 THEN '💎 Platinum'
              WHEN t.tier_level = 5 THEN '👑 Diamond'
          END as tier_display,
          CASE 
              WHEN t.tier_level = 1 THEN 30
              WHEN t.tier_level = 2 THEN 35
              WHEN t.tier_level = 3 THEN 40
              WHEN t.tier_level = 4 THEN 45
              WHEN t.tier_level = 5 THEN 50
          END as commission_percent
          FROM partner_tiers t
          WHERE t.partner_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tier = mysqli_fetch_assoc($result);

if (!$tier) {
    // Create default tier for partner
    $insert = mysqli_prepare($conn, "INSERT INTO partner_tiers (partner_id, tier_level, tier_name, commission_rate) VALUES (?, 1, 'Bronze', 30)");
    mysqli_stmt_bind_param($insert, "i", $partner_id);
    mysqli_stmt_execute($insert);
    
    $tier = [
        'tier_level' => 1,
        'tier_name' => 'Bronze',
        'commission_rate' => 30,
        'total_conversions' => 0,
        'current_month_conversions' => 0
    ];
}

// Get next tier requirements
$next_tier = null;
if ($tier['tier_level'] < 5) {
    $requirements = [
        2 => ['name' => 'Silver', 'conversions' => 10, 'commission' => 35],
        3 => ['name' => 'Gold', 'conversions' => 25, 'commission' => 40],
        4 => ['name' => 'Platinum', 'conversions' => 50, 'commission' => 45],
        5 => ['name' => 'Diamond', 'conversions' => 75, 'commission' => 50]
    ];
    $next_tier = $requirements[$tier['tier_level'] + 1];
    $next_tier['conversions_needed'] = max(0, $next_tier['conversions'] - $tier['total_conversions']);
}

echo json_encode([
    'success' => true,
    'current_tier' => [
        'level' => $tier['tier_level'],
        'name' => $tier['tier_name'],
        'display' => $tier['tier_display'] ?? '🥉 Bronze',
        'commission_rate' => $tier['commission_rate'],
        'commission_percent' => $tier['commission_percent'] ?? 30,
        'total_conversions' => (int)$tier['total_conversions'],
        'monthly_conversions' => (int)$tier['current_month_conversions']
    ],
    'next_tier' => $next_tier,
    'tiers' => [
        1 => ['name' => 'Bronze', 'commission' => 30, 'conversions_needed' => 0],
        2 => ['name' => 'Silver', 'commission' => 35, 'conversions_needed' => 10],
        3 => ['name' => 'Gold', 'commission' => 40, 'conversions_needed' => 25],
        4 => ['name' => 'Platinum', 'commission' => 45, 'conversions_needed' => 50],
        5 => ['name' => 'Diamond', 'commission' => 50, 'conversions_needed' => 75]
    ]
]);

mysqli_close($conn);
?>