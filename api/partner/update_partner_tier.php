<?php
// api/partner/update_partner_tier.php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'DB error']));
}

$input = json_decode(file_get_contents('php://input'), true);
$partner_id = $input['partner_id'] ?? 0;
$action = '';

if (isset($input['recalculate']) && $input['recalculate']) {
    $action = 'recalculate';
} elseif (isset($input['remove_override']) && $input['remove_override']) {
    $action = 'remove_override';
} elseif (isset($input['tier_id'])) {
    $action = 'manual_set';
}

if (!$partner_id) {
    echo json_encode(['success' => false, 'error' => 'Partner ID required']);
    exit;
}

// Get partner's conversion count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE partner_id = ? AND status = 'converted'");
$stmt->execute([$partner_id]);
$conversions = (int)$stmt->fetch()['total'];

// Get tier definitions
$stmt = $pdo->query("SELECT * FROM tier_benefits WHERE is_active = 1 ORDER BY min_conversions ASC");
$tiers = $stmt->fetchAll();

// Calculate tier based on conversions
function calculateTier($conversions, $tiers) {
    $tier = $tiers[0] ?? null;
    foreach ($tiers as $t) {
        if ($conversions >= $t['min_conversions'] && $conversions <= $t['max_conversions']) {
            $tier = $t;
        }
    }
    return $tier;
}

// Get current partner data
$stmt = $pdo->prepare("SELECT tier_id, manual_tier_override, manual_tier_id FROM partners WHERE id = ?");
$stmt->execute([$partner_id]);
$partner = $stmt->fetch();

if (!$partner) {
    echo json_encode(['success' => false, 'error' => 'Partner not found']);
    exit;
}

$old_tier_id = $partner['tier_id'] ?? 1;
$response = ['success' => true];

switch ($action) {
    case 'recalculate':
        // Only recalculate if not manually overridden
        if ($partner['manual_tier_override'] && $partner['manual_tier_id']) {
            $new_tier_id = $partner['manual_tier_id'];
            $response['message'] = 'Manual override active - tier unchanged';
        } else {
            $new_tier = calculateTier($conversions, $tiers);
            $new_tier_id = $new_tier['tier_id'];
            
            if ($old_tier_id != $new_tier_id) {
                // Log change
                $stmt = $pdo->prepare("INSERT INTO tier_log (partner_id, old_tier_id, new_tier_id, conversions_at_time, changed_by) VALUES (?, ?, ?, ?, 'system')");
                $stmt->execute([$partner_id, $old_tier_id, $new_tier_id, $conversions]);
                
                // Update partner
                $stmt = $pdo->prepare("UPDATE partners SET tier_id = ?, tier_updated_at = NOW(), total_conversions = ? WHERE id = ?");
                $stmt->execute([$new_tier_id, $conversions, $partner_id]);
                
                $response['tier_name'] = $new_tier['tier_name'];
                $response['message'] = 'Tier updated to ' . $new_tier['tier_name'];
            } else {
                // Just update conversion count
                $stmt = $pdo->prepare("UPDATE partners SET total_conversions = ? WHERE id = ?");
                $stmt->execute([$conversions, $partner_id]);
                $response['message'] = 'Tier unchanged, conversions updated';
            }
        }
        break;
        
    case 'manual_set':
        $tier_id = (int)$input['tier_id'];
        $notes = $input['notes'] ?? '';
        $manual_override = isset($input['manual_override']) ? (bool)$input['manual_override'] : true;
        
        // Log change
        $stmt = $pdo->prepare("INSERT INTO tier_log (partner_id, old_tier_id, new_tier_id, conversions_at_time, changed_by, changed_by_user_id, notes) VALUES (?, ?, ?, ?, 'admin', ?, ?)");
        $stmt->execute([$partner_id, $old_tier_id, $tier_id, $conversions, $_SESSION['user_id'], $notes]);
        
        // Update partner
        $stmt = $pdo->prepare("UPDATE partners SET tier_id = ?, manual_tier_override = ?, manual_tier_id = ?, tier_updated_at = NOW() WHERE id = ?");
        $stmt->execute([$tier_id, $manual_override ? 1 : 0, $manual_override ? $tier_id : null, $partner_id]);
        
        // Get tier name for response
        $tierName = '';
        foreach ($tiers as $t) {
            if ($t['tier_id'] == $tier_id) {
                $tierName = $t['tier_name'];
                break;
            }
        }
        
        $response['tier_name'] = $tierName;
        $response['message'] = 'Tier manually set to ' . $tierName;
        break;
        
    case 'remove_override':
        // Calculate auto tier
        $new_tier = calculateTier($conversions, $tiers);
        $new_tier_id = $new_tier['tier_id'];
        
        $stmt = $pdo->prepare("UPDATE partners SET manual_tier_override = FALSE, manual_tier_id = NULL, tier_id = ? WHERE id = ?");
        $stmt->execute([$new_tier_id, $partner_id]);
        
        $response['tier_name'] = $new_tier['tier_name'];
        $response['message'] = 'Manual override removed, auto tier: ' . $new_tier['tier_name'];
        break;
        
    default:
        $response['success'] = false;
        $response['error'] = 'Invalid action';
}

echo json_encode($response);