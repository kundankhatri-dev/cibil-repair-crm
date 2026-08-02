<?php
// ============================================================
// GET PARTNER DASHBOARD DATA - API
// ============================================================
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$isAdmin = in_array($_SESSION['user_role'], ['admin', 'super_admin']);
$partner_id = isset($_GET['partner_id']) ? (int)$_GET['partner_id'] : 0;

// If admin view, allow viewing any partner's data
if ($isAdmin && $partner_id > 0) {
    $viewing_partner_id = $partner_id;
} else {
    // Regular partner can only view their own data
    if ($_SESSION['user_role'] !== 'partner') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $viewing_partner_id = (int)$_SESSION['user_id'];
}

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

try {
    // Get total leads
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE partner_id = ?");
    $stmt->execute([$viewing_partner_id]);
    $totalLeads = $stmt->fetch()['total'] ?? 0;
    
    // Get converted leads
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE partner_id = ? AND status = 'converted'");
    $stmt->execute([$viewing_partner_id]);
    $convertedLeads = $stmt->fetch()['total'] ?? 0;
    
    // Get total commission
    $stmt = $pdo->prepare("SELECT SUM(commission_amount) as total FROM commissions WHERE partner_id = ? AND status = 'paid'");
    $stmt->execute([$viewing_partner_id]);
    $totalCommission = $stmt->fetch()['total'] ?? 0;
    
    // Get pending payouts
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payouts WHERE partner_id = ? AND status = 'pending'");
    $stmt->execute([$viewing_partner_id]);
    $pendingPayout = $stmt->fetch()['total'] ?? 0;
    
    // Get followups due
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM followups WHERE partner_id = ? AND status = 'pending' AND follow_up_date <= NOW()");
    $stmt->execute([$viewing_partner_id]);
    $followupsDue = $stmt->fetch()['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'total_leads' => (int)$totalLeads,
        'converted_customers' => (int)$convertedLeads,
        'total_commission' => (float)$totalCommission,
        'pending_payout' => (float)$pendingPayout,
        'followups_due' => (int)$followupsDue,
        'new_leads' => 0,
        'contacted_leads' => 0,
        'lost_leads' => 0,
        'monthly_commission' => [],
        'recent_activity' => []
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>