<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$priority = isset($_GET['priority']) ? $_GET['priority'] : 'all';

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$query = "SELECT id, customer_name, customer_phone, service, status, created_at,
          COALESCE(lead_score, 0) as score,
          COALESCE(lead_priority, 'low') as priority
          FROM partner_leads 
          WHERE partner_id = $partner_id";

if ($priority != 'all') {
    $query .= " AND lead_priority = '$priority'";
}

$query .= " ORDER BY lead_score DESC, created_at ASC LIMIT 50";

$result = mysqli_query($conn, $query);
$leads = mysqli_fetch_all($result, MYSQLI_ASSOC);

foreach ($leads as &$lead) {
    if ($lead['priority'] == 'urgent') $lead['priority_label'] = '🔴 Urgent';
    elseif ($lead['priority'] == 'high') $lead['priority_label'] = '🟠 High';
    elseif ($lead['priority'] == 'medium') $lead['priority_label'] = '🟡 Medium';
    else $lead['priority_label'] = '⚪ Low';
}

echo json_encode([
    'success' => true,
    'leads' => $leads,
    'total' => count($leads)
]);

mysqli_close($conn);
?>