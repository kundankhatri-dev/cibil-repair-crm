<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$lead_id = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : 0;

if ($lead_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Lead ID required']);
    exit;
}

// Database connection
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get lead details
$query = "SELECT id, customer_name, service, status, 
          DATEDIFF(NOW(), created_at) as days_old,
          COALESCE(call_count, 0) as call_count
          FROM partner_leads 
          WHERE id = $lead_id AND partner_id = $partner_id";

$result = mysqli_query($conn, $query);
$lead = mysqli_fetch_assoc($result);

if (!$lead) {
    echo json_encode(['success' => false, 'error' => 'Lead not found']);
    mysqli_close($conn);
    exit;
}

// Calculate score
$score = 50;

// Service scoring
$service = $lead['service'];
if ($service == 'Suit Filed Clearance') $score += 30;
elseif ($service == 'Written Off Clearance') $score += 25;
elseif ($service == 'Wrong Entry Clearance') $score += 20;
elseif ($service == 'Settled Clearance') $score += 20;
elseif ($service == 'Credit Report Analysis') $score += 15;
elseif ($service == 'Profile Correction') $score += 10;
else $score += 5;

// Age scoring
if ($lead['days_old'] <= 3) $score += 25;
elseif ($lead['days_old'] <= 7) $score += 15;
elseif ($lead['days_old'] <= 14) $score += 5;

// Status scoring
if ($lead['status'] == 'new') $score += 20;
elseif ($lead['status'] == 'contacted') $score += 10;

// Call count scoring
if ($lead['call_count'] >= 3) $score += 10;
elseif ($lead['call_count'] >= 1) $score += 5;

// Cap score
$score = min(100, max(0, $score));

// Priority
if ($score >= 70) $priority = 'urgent';
elseif ($score >= 50) $priority = 'high';
elseif ($score >= 30) $priority = 'medium';
else $priority = 'low';

// Update lead
$update = "UPDATE partner_leads SET lead_score = $score, lead_priority = '$priority' WHERE id = $lead_id";
mysqli_query($conn, $update);

// Priority label
if ($priority == 'urgent') $priority_label = '🔴 Urgent';
elseif ($priority == 'high') $priority_label = '🟠 High';
elseif ($priority == 'medium') $priority_label = '🟡 Medium';
else $priority_label = '⚪ Low';

echo json_encode([
    'success' => true,
    'lead_id' => $lead_id,
    'lead_name' => $lead['customer_name'],
    'score' => $score,
    'priority' => $priority,
    'priority_label' => $priority_label,
    'factors' => [
        'service' => $lead['service'],
        'days_old' => $lead['days_old'],
        'status' => $lead['status'],
        'call_count' => $lead['call_count']
    ]
]);

mysqli_close($conn);
?>