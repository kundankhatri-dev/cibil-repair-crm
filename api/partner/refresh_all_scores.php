<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$partner_id = $_SESSION['user_id'];

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$leadsTable = 'leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'partner_leads'");
if (mysqli_num_rows($checkTable) > 0) {
    $leadsTable = 'partner_leads';
}

// Get all leads for this partner
$query = "SELECT id, service_type, source, status, created_at FROM $leadsTable 
          WHERE partner_id = ? AND status != 'converted'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leads = mysqli_fetch_all($result, MYSQLI_ASSOC);

$count = 0;
foreach ($leads as $lead) {
    // Calculate score (same logic as calculate_score.php)
    $score = 0;
    
    $service_scores = [
        'Written Off Clearance' => 30,
        'Suit Filed Clearance' => 30,
        'Settled Clearance' => 20,
        'Credit Report Analysis' => 15,
        'Profile Correction' => 10,
        'Wrong Entry Clearance' => 15
    ];
    $score += $service_scores[$lead['service_type']] ?? 10;
    
    $source_scores = [
        'Referral' => 25,
        'Call' => 20,
        'Website' => 15,
        'Social Media' => 12,
        'Walk-in' => 10
    ];
    $score += $source_scores[$lead['source']] ?? 10;
    
    $days_old = (time() - strtotime($lead['created_at'])) / 86400;
    if ($days_old <= 1) $score += 30;
    elseif ($days_old <= 3) $score += 20;
    elseif ($days_old <= 7) $score += 15;
    elseif ($days_old <= 14) $score += 10;
    elseif ($days_old <= 30) $score += 5;
    
    if ($lead['status'] === 'contacted') $score += 10;
    
    $priority = 'low';
    if ($score >= 70) $priority = 'urgent';
    elseif ($score >= 50) $priority = 'high';
    elseif ($score >= 30) $priority = 'medium';
    
    $score = min(100, $score);
    
    $update = "UPDATE $leadsTable SET score = ?, priority = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($update_stmt, "isi", $score, $priority, $lead['id']);
    mysqli_stmt_execute($update_stmt);
    $count++;
}

echo json_encode(['success' => true, 'count' => $count]);

mysqli_close($conn);
?>