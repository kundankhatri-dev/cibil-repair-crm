<?php
// ============================================================
// FILE: /api/admin/get_partner_details_admin.php
// Fetches detailed partner data for admin view
// ============================================================

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Partner ID required']);
    exit();
}

try {
    $pdo = getPDO();
    
    // Get partner details
    $stmt = $pdo->prepare("
        SELECT 
            u.id as user_id,
            u.name as user_name,
            u.email,
            u.phone,
            u.status as user_status,
            u.created_at as user_created_at,
            p.id as partner_id,
            p.company_name,
            p.location,
            p.commission_rate,
            p.status as partner_status,
            p.tier,
            p.allow_payouts,
            p.allow_referrals,
            p.created_at as partner_created_at
        FROM users u
        LEFT JOIN partners p ON u.id = p.user_id
        WHERE u.id = ? AND u.role = 'partner'
    ");
    $stmt->execute([$user_id]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partner) {
        echo json_encode(['success' => false, 'error' => 'Partner not found']);
        exit();
    }
    
    // Get stats
    $statsStmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM leads WHERE partner_id = ?) as total_leads,
            (SELECT COUNT(*) FROM leads WHERE partner_id = ? AND status = 'converted') as converted_leads,
            (SELECT COALESCE(SUM(commission_amount), 0) FROM partner_commissions WHERE partner_id = ?) as total_commission,
            (SELECT COALESCE(SUM(amount), 0) FROM payout_requests WHERE partner_id = ? AND status = 'pending') as pending_payout
    ");
    $statsStmt->execute([$user_id, $user_id, $user_id, $user_id]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    $partner['total_leads'] = $stats['total_leads'] ?? 0;
    $partner['converted_leads'] = $stats['converted_leads'] ?? 0;
    $partner['total_commission'] = $stats['total_commission'] ?? 0;
    $partner['pending_payout'] = $stats['pending_payout'] ?? 0;
    
    // Get lead sources
    $sourceStmt = $pdo->prepare("
        SELECT source_type, COUNT(*) as count 
        FROM leads 
        WHERE partner_id = ? 
        GROUP BY source_type
    ");
    $sourceStmt->execute([$user_id]);
    $partner['lead_sources'] = $sourceStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get monthly leads
    $monthlyStmt = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
        FROM leads 
        WHERE partner_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyStmt->execute([$user_id]);
    $partner['monthly_leads'] = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activity
    $activityStmt = $pdo->prepare("
        SELECT action, details, created_at 
        FROM activity_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $activityStmt->execute([$user_id]);
    $partner['recent_activity'] = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $partner
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching partner details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch partner details'
    ]);
}
?>