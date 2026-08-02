<?php
// ============================================================
// FILE: /api/admin/get_all_partner_data.php
// Fetches ALL partner data for admin view
// ============================================================

// Adjust the path to your config file based on file location
// Since this is in /api/admin/, we need to go up two levels
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

// Check if admin is logged in
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    $pdo = getPDO();
    
    // Get all partners with their data
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
            p.created_at as partner_created_at,
            (SELECT COUNT(*) FROM leads WHERE partner_id = u.id) as total_leads,
            (SELECT COUNT(*) FROM leads WHERE partner_id = u.id AND status = 'converted') as converted_leads,
            (SELECT COALESCE(SUM(commission_amount), 0) FROM partner_commissions WHERE partner_id = u.id) as total_commission,
            (SELECT COALESCE(SUM(amount), 0) FROM payout_requests WHERE partner_id = u.id AND status = 'pending') as pending_payout
        FROM users u
        LEFT JOIN partners p ON u.id = p.user_id
        WHERE u.role = 'partner'
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get additional stats for each partner
    foreach ($partners as &$partner) {
        $partner_id = $partner['user_id'];
        
        // Get recent activity
        $stmt2 = $pdo->prepare("
            SELECT action, details, created_at 
            FROM activity_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt2->execute([$partner_id]);
        $partner['recent_activity'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        // Get lead sources breakdown
        $stmt3 = $pdo->prepare("
            SELECT source_type, COUNT(*) as count 
            FROM leads 
            WHERE partner_id = ? 
            GROUP BY source_type
        ");
        $stmt3->execute([$partner_id]);
        $partner['lead_sources'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        
        // Get monthly lead data
        $stmt4 = $pdo->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
            FROM leads 
            WHERE partner_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt4->execute([$partner_id]);
        $partner['monthly_leads'] = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get overall partner stats
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total_partners,
            SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as active_partners,
            SUM(CASE WHEN u.status = 'inactive' THEN 1 ELSE 0 END) as inactive_partners,
            SUM(CASE WHEN u.status = 'pending' THEN 1 ELSE 0 END) as pending_partners,
            (SELECT COUNT(*) FROM users WHERE role = 'partner' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_partners_30d,
            (SELECT COALESCE(SUM(commission_amount), 0) FROM partner_commissions) as total_commission_paid
        FROM users u
        WHERE u.role = 'partner'
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'partners' => $partners,
            'stats' => $stats,
            'total' => count($partners)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching partner data: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch partner data: ' . $e->getMessage()
    ]);
}
?>