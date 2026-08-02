<?php
// api/partner/auto_payout.php
// Automatically process payouts based on thresholds

session_start();
require_once '../config.php';

// Run daily via cron: 0 0 * * * php auto_payout.php

$payoutsTable = 'partner_payouts';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$payoutsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE $payoutsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'paid', 'rejected') DEFAULT 'pending',
        paid_date TIMESTAMP NULL,
        auto_processed TINYINT(1) DEFAULT 0,
        INDEX idx_partner (partner_id),
        INDEX idx_status (status)
    )";
    mysqli_query($conn, $createTable);
}

// Get partners with auto-payout enabled
$scheduleTable = 'payout_schedules';
$checkSchedule = mysqli_query($conn, "SHOW TABLES LIKE '$scheduleTable'");
if (mysqli_num_rows($checkSchedule) > 0) {
    $query = "SELECT ps.partner_id, ps.threshold_amount, u.name, u.email
              FROM $scheduleTable ps
              JOIN users u ON ps.partner_id = u.id
              WHERE ps.auto_payout = 1 AND ps.threshold_amount > 0";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $partners = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    $payouts_created = 0;
    foreach ($partners as $partner) {
        // Calculate available balance
        $leadsTable = 'partner_leads';
        $checkLeads = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
        if (mysqli_num_rows($checkLeads) == 0) {
            $leadsTable = 'leads';
        }
        
        $balance_query = "SELECT SUM(commission_amount) as balance FROM $leadsTable 
                          WHERE partner_id = ? AND status = 'converted' AND paid_status != 'paid'";
        $balance_stmt = mysqli_prepare($conn, $balance_query);
        mysqli_stmt_bind_param($balance_stmt, "i", $partner['partner_id']);
        mysqli_stmt_execute($balance_stmt);
        $balance_result = mysqli_stmt_get_result($balance_stmt);
        $balance = mysqli_fetch_assoc($balance_result);
        
        $available = $balance['balance'] ?? 0;
        
        if ($available >= $partner['threshold_amount']) {
            // Auto-create payout request
            $payout_amount = min($available, $partner['threshold_amount'] * 2);
            
            $insert = mysqli_prepare($conn, "INSERT INTO $payoutsTable (partner_id, amount, status, auto_processed) VALUES (?, ?, 'pending', 1)");
            mysqli_stmt_bind_param($insert, "id", $partner['partner_id'], $payout_amount);
            mysqli_stmt_execute($insert);
            $payouts_created++;
            
            // Notify partner
            $subject = "Auto-Payout Request Created";
            $message = "Dear {$partner['name']},\n\n";
            $message .= "An automatic payout request of ₹" . number_format($payout_amount, 2) . " has been created based on your auto-payout settings.\n";
            $message .= "The request will be processed within 3-5 business days.\n\n";
            $message .= "Thank you,\nCIBIL Repair Team";
            
            // mail($partner['email'], $subject, $message, "From: payouts@cibilrepair.in");
        }
    }
    
    echo json_encode([
        'success' => true,
        'auto_payouts_created' => $payouts_created,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Auto-payout not configured']);
}

mysqli_close($conn);
?>