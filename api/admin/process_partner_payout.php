<?php
// ============================================================
// FILE: /api/admin/process_partner_payout.php
// Processes partner payouts
// ============================================================

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

$input = json_decode(file_get_contents('php://input'), true);
$partner_id = isset($input['partner_id']) ? (int)$input['partner_id'] : 0;

if (!$partner_id) {
    echo json_encode(['success' => false, 'error' => 'Partner ID required']);
    exit();
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();
    
    // Get all pending payouts for this partner
    $stmt = $pdo->prepare("
        SELECT id, amount 
        FROM payout_requests 
        WHERE partner_id = ? AND status = 'pending'
    ");
    $stmt->execute([$partner_id]);
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pending)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'No pending payouts for this partner']);
        exit();
    }
    
    $total_amount = array_sum(array_column($pending, 'amount'));
    
    // Update all pending payouts to 'completed'
    $updateStmt = $pdo->prepare("
        UPDATE payout_requests 
        SET status = 'completed', processed_date = NOW() 
        WHERE partner_id = ? AND status = 'pending'
    ");
    $updateStmt->execute([$partner_id]);
    
    // Log the activity
    $logStmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, user_name, action, details, ip_address, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $admin_name = $_SESSION['user_name'] ?? 'Admin';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $logStmt->execute([
        $_SESSION['user_id'],
        $admin_name,
        'Payout Processed',
        "Processed payouts for partner ID {$partner_id}. Total: ₹" . number_format($total_amount, 2),
        $ip
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payouts processed successfully',
        'data' => [
            'processed_count' => count($pending),
            'total_amount' => $total_amount
        ]
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error processing payout: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to process payouts: ' . $e->getMessage()
    ]);
}
?>