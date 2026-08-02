<?php
// api/cron/update_tiers_cron.php
// Run this monthly to update partner tiers

require_once '../config.php';

$tiers = [
    1 => ['min_conversions' => 0, 'commission' => 30, 'name' => 'Bronze'],
    2 => ['min_conversions' => 10, 'commission' => 35, 'name' => 'Silver'],
    3 => ['min_conversions' => 25, 'commission' => 40, 'name' => 'Gold'],
    4 => ['min_conversions' => 50, 'commission' => 45, 'name' => 'Platinum'],
    5 => ['min_conversions' => 75, 'commission' => 50, 'name' => 'Diamond']
];

// Get all partners with their total conversions
$query = "SELECT p.user_id as partner_id, 
          COUNT(CASE WHEN l.status = 'converted' THEN 1 END) as total_conversions
          FROM partners p
          LEFT JOIN partner_leads l ON p.user_id = l.partner_id
          GROUP BY p.user_id";

$result = mysqli_query($conn, $query);
$partners = mysqli_fetch_all($result, MYSQLI_ASSOC);
$updated = 0;

foreach ($partners as $partner) {
    $conversions = (int)$partner['total_conversions'];
    
    // Determine tier
    $new_tier = 1;
    $new_commission = 30;
    $new_name = 'Bronze';
    
    if ($conversions >= 75) {
        $new_tier = 5;
        $new_commission = 50;
        $new_name = 'Diamond';
    } elseif ($conversions >= 50) {
        $new_tier = 4;
        $new_commission = 45;
        $new_name = 'Platinum';
    } elseif ($conversions >= 25) {
        $new_tier = 3;
        $new_commission = 40;
        $new_name = 'Gold';
    } elseif ($conversions >= 10) {
        $new_tier = 2;
        $new_commission = 35;
        $new_name = 'Silver';
    }
    
    $update = mysqli_prepare($conn, "UPDATE partner_tiers SET tier_level = ?, tier_name = ?, commission_rate = ?, total_conversions = ?, tier_updated_at = NOW() WHERE partner_id = ?");
    mysqli_stmt_bind_param($update, "isdis", $new_tier, $new_name, $new_commission, $conversions, $partner['partner_id']);
    mysqli_stmt_execute($update);
    
    $updated++;
}

echo date('Y-m-d H:i:s') . " - Updated $updated partner tiers\n";

mysqli_close($conn);
?>