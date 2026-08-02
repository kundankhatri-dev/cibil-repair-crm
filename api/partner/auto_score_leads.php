<?php
// api/partner/auto_score_leads.php
// Automatically score leads every hour

session_start();
require_once '../config.php';

// Run via cron: 0 * * * * php auto_score_leads.php

$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// Get all un-scored leads
$query = "SELECT id, customer_name, customer_phone, service_type, source, status, created_at 
          FROM $leadsTable 
          WHERE score IS NULL OR score = 0";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leads = mysqli_fetch_all($result, MYSQLI_ASSOC);

$scored = 0;
foreach ($leads as $lead) {
    // Calculate score based on multiple factors
    $score = 0;
    $priority = 'low';
    
    // Service type scoring
    $service_scores = [
        'Written Off Clearance' => 25,
        'Suit Filed Clearance' => 30,
        'Settled Clearance' => 20,
        'Credit Report Analysis' => 15,
        'Profile Correction' => 10
    ];
    $score += $service_scores[$lead['service_type']] ?? 10;
    
    // Source scoring
    $source_scores = [
        'Referral' => 15,
        'Call' => 12,
        'Website' => 10,
        'Social Media' => 8
    ];
    $score += $source_scores[$lead['source']] ?? 5;
    
    // Age scoring (newer = higher score)
    $days_old = (time() - strtotime($lead['created_at'])) / 86400;
    if ($days_old <= 1) $score += 20;
    elseif ($days_old <= 3) $score += 15;
    elseif ($days_old <= 7) $score += 10;
    elseif ($days_old <= 14) $score += 5;
    
    // Status adjustment
    if ($lead['status'] === 'contacted') $score += 10;
    if ($lead['status'] === 'converted') $score = 100;
    
    // Determine priority
    if ($score >= 70) $priority = 'urgent';
    elseif ($score >= 50) $priority = 'high';
    elseif ($score >= 30) $priority = 'medium';
    else $priority = 'low';
    
    // Update lead with score
    $update = mysqli_prepare($conn, "UPDATE $leadsTable SET score = ?, priority = ? WHERE id = ?");
    mysqli_stmt_bind_param($update, "isi", $score, $priority, $lead['id']);
    mysqli_stmt_execute($update);
    $scored++;
}

echo json_encode([
    'success' => true,
    'leads_scored' => $scored,
    'timestamp' => date('Y-m-d H:i:s')
]);

mysqli_close($conn);
?>