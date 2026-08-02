<?php
// api/partner/payout_schedule.php
// Manage payout schedules and auto-payout settings

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'view';

// Create payout schedule table
$scheduleTable = 'payout_schedules';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$scheduleTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE $scheduleTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL UNIQUE,
        auto_payout TINYINT(1) DEFAULT 0,
        threshold_amount DECIMAL(10,2) DEFAULT 1000,
        payout_day INT DEFAULT 1,
        last_payout_date DATE,
        next_payout_date DATE,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createTable);
}

if ($action === 'view') {
    $query = "SELECT * FROM $scheduleTable WHERE partner_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $partner_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $schedule = mysqli_fetch_assoc($result);
    
    if (!$schedule) {
        $schedule = [
            'auto_payout' => 0,
            'threshold_amount' => 1000,
            'payout_day' => 1,
            'last_payout_date' => null,
            'next_payout_date' => date('Y-m-01', strtotime('+1 month'))
        ];
    }
    
    // Get available balance
    $leadsTable = 'partner_leads';
    $checkLeadsTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
    if (mysqli_num_rows($checkLeadsTable) == 0) {
        $leadsTable = 'leads';
    }
    
    $balance_query = "SELECT SUM(commission_amount) as balance FROM $leadsTable WHERE partner_id = ? AND status = 'converted' AND paid_status != 'paid'";
    $balance_stmt = mysqli_prepare($conn, $balance_query);
    mysqli_stmt_bind_param($balance_stmt, "i", $partner_id);
    mysqli_stmt_execute($balance_stmt);
    $balance_result = mysqli_stmt_get_result($balance_stmt);
    $balance = mysqli_fetch_assoc($balance_result);
    
    echo json_encode([
        'success' => true,
        'schedule' => $schedule,
        'current_balance' => round($balance['balance'] ?? 0, 2),
        'next_payout_amount' => min($balance['balance'] ?? 0, 10000),
        'can_auto_payout' => ($balance['balance'] ?? 0) >= $schedule['threshold_amount']
    ]);
    
} elseif ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $auto_payout = $data['auto_payout'] ?? 0;
    $threshold = $data['threshold_amount'] ?? 1000;
    $payout_day = min(28, max(1, $data['payout_day'] ?? 1));
    
    $insert = mysqli_prepare($conn, "INSERT INTO $scheduleTable (partner_id, auto_payout, threshold_amount, payout_day) 
        VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE 
        auto_payout = VALUES(auto_payout), threshold_amount = VALUES(threshold_amount), payout_day = VALUES(payout_day)");
    mysqli_stmt_bind_param($insert, "iidi", $partner_id, $auto_payout, $threshold, $payout_day);
    
    if (mysqli_stmt_execute($insert)) {
        echo json_encode([
            'success' => true,
            'message' => 'Payout settings updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update settings']);
    }
}

mysqli_close($conn);
?>