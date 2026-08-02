<?php
// ============================================================
// /cron/update_partner_tiers.php
// Monthly cron job to update partner tiers
// Runs on the 1st of every month at 12:00 AM
// ============================================================

// Prevent direct access from browser
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// ============================================================
// DATABASE CONNECTION
// ============================================================
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die('❌ Database connection failed: ' . $e->getMessage() . "\n");
}

// ============================================================
// TIER DEFINITIONS (Matches your PDF)
// ============================================================
$tiers = [
    1 => ['name' => 'Bronze',   'icon' => '🥉', 'commission' => 20, 'min' => 0,   'max' => 5],
    2 => ['name' => 'Silver',   'icon' => '🥈', 'commission' => 25, 'min' => 6,   'max' => 15],
    3 => ['name' => 'Gold',     'icon' => '🥇', 'commission' => 30, 'min' => 16,  'max' => 30],
    4 => ['name' => 'Platinum', 'icon' => '💎', 'commission' => 35, 'min' => 31,  'max' => 50],
    5 => ['name' => 'Diamond',  'icon' => '💠', 'commission' => 40, 'min' => 51,  'max' => 999]
];

// ============================================================
// UPDATE PARTNER TIERS FUNCTION
// ============================================================
function updatePartnerTiers($pdo, $tiers) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  📊 PARTNER TIER UPDATE - " . date('d M Y, h:i A') . "\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // Check if tables exist
    $tables = ['partners', 'leads', 'users'];
    foreach ($tables as $table) {
        $check = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($check->rowCount() == 0) {
            echo "❌ Error: '$table' table does not exist.\n";
            return ['updated' => 0, 'errors' => ["$table table not found"]];
        }
    }
    
    // Get all partners with their current tier
    $stmt = $pdo->query("
        SELECT 
            u.id, 
            u.name, 
            u.email,
            p.tier_id as current_tier,
            p.monthly_referrals as current_monthly
        FROM users u
        LEFT JOIN partners p ON u.id = p.user_id
        WHERE u.role = 'partner'
    ");
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($partners)) {
        echo "ℹ️  No partners found in the system.\n";
        return ['updated' => 0, 'errors' => []];
    }
    
    echo "Found " . count($partners) . " partners\n";
    echo str_repeat('─', 70) . "\n\n";
    
    $updated = 0;
    $errors = [];
    $log = [];
    $total_conversions = 0;
    
    foreach ($partners as $partner) {
        try {
            // Count conversions this month
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM leads 
                WHERE partner_id = ? 
                AND status = 'converted' 
                AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                AND YEAR(created_at) = YEAR(CURRENT_DATE())");
            $stmt->execute([$partner['id']]);
            $monthly_count = (int)$stmt->fetchColumn();
            $total_conversions += $monthly_count;
            
            // Determine tier based on monthly referrals
            $tier_id = 1; // Default: Bronze
            $tier_name = 'Bronze';
            
            foreach ($tiers as $id => $tier) {
                if ($monthly_count >= $tier['min'] && $monthly_count <= $tier['max']) {
                    $tier_id = $id;
                    $tier_name = $tier['name'];
                    break;
                }
            }
            
            $current_tier_id = (int)($partner['current_tier'] ?? 1);
            $current_tier_name = $tiers[$current_tier_id]['name'] ?? 'Unknown';
            $is_upgrade = $tier_id > $current_tier_id;
            $is_downgrade = $tier_id < $current_tier_id;
            
            // Update partner tier
            $stmt = $pdo->prepare("UPDATE partners SET 
                tier_id = ?, 
                monthly_referrals = ?, 
                tier_updated_at = NOW() 
                WHERE user_id = ?");
            $stmt->execute([$tier_id, $monthly_count, $partner['id']]);
            
            $updated++;
            
            // Prepare log message
            $status = '';
            if ($is_upgrade) {
                $status = "⬆️ UPGRADE";
            } elseif ($is_downgrade) {
                $status = "⬇️ DOWNGRADE";
            } else {
                $status = "➡️ UNCHANGED";
            }
            
            $log[] = sprintf(
                "%-25s | %s | %s → %s | %d conversions | %s",
                substr($partner['name'], 0, 25),
                $status,
                $current_tier_name,
                $tier_name,
                $monthly_count,
                $partner['email']
            );
            
        } catch (PDOException $e) {
            $errors[] = "❌ Error updating partner {$partner['id']} ({$partner['name']}): " . $e->getMessage();
        }
    }
    
    // Output results
    echo implode("\n", $log);
    
    if (!empty($errors)) {
        echo "\n\n⚠️ ERRORS:\n";
        echo implode("\n", $errors);
    }
    
    echo "\n\n" . str_repeat('─', 70) . "\n";
    echo "📊 SUMMARY:\n";
    echo "  ✅ Updated: {$updated} partners\n";
    echo "  📈 Total conversions this month: {$total_conversions}\n";
    echo "  🏆 Active tiers: " . count($tiers) . " (Bronze → Diamond)\n";
    echo "  📅 Next update: " . date('d M Y', strtotime('+1 month')) . "\n";
    
    // Show tier distribution
    echo "\n🏅 TIER DISTRIBUTION:\n";
    $tierStats = $pdo->query("
        SELECT 
            p.tier_id,
            COUNT(*) as count,
            SUM(p.monthly_referrals) as total_refs
        FROM partners p
        GROUP BY p.tier_id
        ORDER BY p.tier_id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tierStats as $stat) {
        $tier_name = $tiers[$stat['tier_id']]['name'] ?? 'Unknown';
        $icon = $tiers[$stat['tier_id']]['icon'] ?? '📌';
        echo "  {$icon} {$tier_name}: {$stat['count']} partners, {$stat['total_refs']} total conversions\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    
    return [
        'updated' => $updated,
        'total_conversions' => $total_conversions,
        'errors' => $errors,
        'log' => $log
    ];
}

// ============================================================
// RUN THE UPDATE
// ============================================================
echo "Starting partner tier update...\n";
$result = updatePartnerTiers($pdo, $tiers);

// ============================================================
// LOG TO FILE (Optional)
// ============================================================
$logFile = __DIR__ . '/logs/tier_update.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logEntry = date('Y-m-d H:i:s') . " | Updated: {$result['updated']} | Total conversions: {$result['total_conversions']}\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

echo "\n✅ Done.\n";
exit(0);
?>