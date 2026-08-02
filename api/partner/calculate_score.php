<?php
// ============================================================
// API: Calculate Lead Score
// File: api/partner/calculate_score.php
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];
$lead_id = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : 0;

if ($lead_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid lead ID required']);
    exit;
}

// ========== DIRECT DATABASE CONNECTION ==========
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// Get lead data
$query = "SELECT id, name, status, created_at FROM leads WHERE id = $lead_id AND partner_id = $partner_id";
$result = mysqli_query($conn, $query);
$lead = mysqli_fetch_assoc($result);

if (!$lead) {
    echo json_encode(['success' => false, 'error' => 'Lead not found']);
    mysqli_close($conn);
    exit;
}

// ========== CALCULATE SCORE ==========
$score = 50;

$status_bonus = [
    'new' => 20,
    'contacted' => 10,
    'converted' => 30,
    'lost' => 0
];
$score += $status_bonus[$lead['status']] ?? 0;

$created_date = strtotime($lead['created_at']);
$days_old = (time() - $created_date) / (60 * 60 * 24);
if ($days_old <= 2) $score += 20;
elseif ($days_old <= 5) $score += 10;
elseif ($days_old <= 10) $score += 5;

$score = min($score, 100);

if ($score >= 70) $priority = 'urgent';
elseif ($score >= 50) $priority = 'high';
elseif ($score >= 30) $priority = 'medium';
else $priority = 'low';

$update = "UPDATE leads SET score = $score, priority = '$priority' WHERE id = $lead_id";
mysqli_query($conn, $update);

$priority_labels = [
    'urgent' => 'Urgent',
    'high' => 'High',
    'medium' => 'Medium',
    'low' => 'Low'
];

echo json_encode([
    'success' => true,
    'score' => $score,
    'priority' => $priority,
    'priority_label' => $priority_labels[$priority] ?? ucfirst($priority),
    'lead_id' => $lead_id
]);

mysqli_close($conn);
?>