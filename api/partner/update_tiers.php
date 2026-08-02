<?php
// api/partner/update_tiers.php
// Admin only - Update all partner tiers based on conversions

require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check if admin
$check_admin = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
mysqli_stmt_bind_param($check_admin, "i", $_SESSION['user_id']);
mysqli_stmt_execute($check_admin);
$result = mysqli_stmt_get_result($check_admin);
$user = mysqli_fetch_assoc($result);

if ($user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

// Define tier thresholds
$tiers = [
    1 => ['name' => 'Bronze', 'commission' => 30, 'min_conversions' => 0],
    2 => ['name' => 'Silver', 'commission' => 35, 'min_conversions' => 10],
    3 => ['name' => 'Gold', 'commission' => 40, 'min_conversions' => 25],
    4 => ['name' => 'Platinum', 'commission' => 45, 'min_conversions' => 50],
    5 => ['name' => 'Diamond', 'commission' => 50, 'min_conversions' => 75]
];

// Get all partners with their conversion counts
$query = "SELECT p.user_id as partner_id, 
          COUNT(CASE WHEN l.status = 'converted' THEN 1 END) as total_conversions,
          COUNT(CASE WHEN l.status = 'converted' AND MONTH(l.created_at) = MONTH(CURRENT_DATE()) THEN 1 END) as monthly_conversions
          FROM partners p
          LEFT JOIN partner_leads l ON p.user_id = l.partner_id
          GROUP BY p.user_id";

$result = mysqli_query($conn, $query);
$partners = mysqli_fetch_all($result, MYSQLI_ASSOC);

$updated = 0;

foreach ($partners as $partner) {
    $conversions = (int)$partner['total_conversions'];
    
    // Determine tier based on conversions
    $new_tier_level = 1;
    $new_tier_name = 'Bronze';
    $new_commission = 30;
    
    if ($conversions >= 75) {
        $new_tier_level = 5;
        $new_tier_name = 'Diamond';
        $new_commission = 50;
    } elseif ($conversions >= 50) {
        $new_tier_level = 4;
        $new_tier_name = 'Platinum';
        $new_commission = 45;
    } elseif ($conversions >= 25) {
        $new_tier_level = 3;
        $new_tier_name = 'Gold';
        $new_commission = 40;
    } elseif ($conversions >= 10) {
        $new_tier_level = 2;
        $new_tier_name = 'Silver';
        $new_commission = 35;
    }
    
    // Check if partner tier exists
    $check = mysqli_prepare($conn, "SELECT id, tier_level, commission_rate FROM partner_tiers WHERE partner_id = ?");
    mysqli_stmt_bind_param($check, "i", $partner['partner_id']);
    mysqli_stmt_execute($check);
    $check_result = mysqli_stmt_get_result($check);
    $existing = mysqli_fetch_assoc($check_result);
    
    if ($existing) {
        // Update existing
        if ($existing['tier_level'] != $new_tier_level) {
            // Log history
            $history = mysqli_prepare($conn, "INSERT INTO tier_history (partner_id, old_tier, new_tier, old_commission, new_commission) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($history, "iiidd", $partner['partner_id'], $existing['tier_level'], $new_tier_level, $existing['commission_rate'], $new_commission);
            mysqli_stmt_execute($history);
            
            // Update tier
            $update = mysqli_prepare($conn, "UPDATE partner_tiers SET tier_level = ?, tier_name = ?, commission_rate = ?, total_conversions = ?, current_month_conversions = ?, tier_updated_at = NOW() WHERE partner_id = ?");
            mysqli_stmt_bind_param($update, "isdiis", $new_tier_level, $new_tier_name, $new_commission, $conversions, $partner['monthly_conversions'], $partner['partner_id']);
            mysqli_stmt_execute($update);
            $updated++;
        } else {
            // Just update counts
            $update = mysqli_prepare($conn, "UPDATE partner_tiers SET total_conversions = ?, current_month_conversions = ? WHERE partner_id = ?");
            mysqli_stmt_bind_param($update, "iii", $conversions, $partner['monthly_conversions'], $partner['partner_id']);
            mysqli_stmt_execute($update);
        }
    } else {
        // Insert new
        $insert = mysqli_prepare($conn, "INSERT INTO partner_tiers (partner_id, tier_level, tier_name, commission_rate, total_conversions, current_month_conversions) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert, "iisiii", $partner['partner_id'], $new_tier_level, $new_tier_name, $new_commission, $conversions, $partner['monthly_conversions']);
        mysqli_stmt_execute($insert);
        $updated++;
    }
}

echo json_encode([
    'success' => true,
    'message' => "Tiers updated successfully",
    'partners_updated' => $updated,
    'total_partners' => count($partners)
]);

mysqli_close($conn);
?>