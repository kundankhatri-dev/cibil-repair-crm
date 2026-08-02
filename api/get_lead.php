<?php
// ============================================================
// CIBIL REPAIR CRM - Get Single Lead API
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// GET PARAMETERS
// ============================================================

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Lead ID is required']);
    mysqli_close($conn);
    exit;
}

// ============================================================
// GET LEAD
// ============================================================

$result = mysqli_query($conn, "SELECT * FROM leads WHERE id = $id");
$lead = mysqli_fetch_assoc($result);

if (!$lead) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Lead not found']);
    mysqli_close($conn);
    exit;
}

// ============================================================
// GET PARTNER DETAILS
// ============================================================

$partner = null;
if (!empty($lead['partner_id'])) {
    $r = mysqli_query($conn, "SELECT id, name, email, phone FROM partners WHERE id = " . (int)$lead['partner_id']);
    $partner = mysqli_fetch_assoc($r);
}

// ============================================================
// GET ASSIGNED USER
// ============================================================

$assignedUser = null;
if (!empty($lead['assigned_to'])) {
    $r = mysqli_query($conn, "SELECT id, name, email, role FROM users WHERE id = " . (int)$lead['assigned_to']);
    $assignedUser = mysqli_fetch_assoc($r);
}

// ============================================================
// GET ACTIVITY LOGS
// ============================================================

$activities = [];
$r = mysqli_query($conn, "SELECT user_name, action, details, created_at FROM activity_logs WHERE details LIKE '%Lead ID: $id%' ORDER BY created_at DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($r)) {
    $activities[] = $row;
}

// ============================================================
// GET SALES
// ============================================================

$sales = [];
if (!empty($lead['email'])) {
    $r = mysqli_query($conn, "SELECT id, customer_name, service, amount, sale_date, status FROM sales WHERE customer_email = '" . mysqli_real_escape_string($conn, $lead['email']) . "' ORDER BY sale_date DESC LIMIT 5");
    while ($row = mysqli_fetch_assoc($r)) {
        $sales[] = $row;
    }
}

// ============================================================
// CALCULATE STATISTICS
// ============================================================

$leadAge = 0;
if (!empty($lead['created_at'])) {
    $created = new DateTime($lead['created_at']);
    $now = new DateTime();
    $leadAge = $created->diff($now)->days;
}

// ============================================================
// FORMAT RESPONSE
// ============================================================

$formattedLead = [
    'id' => (int)$lead['id'],
    'partner_id' => $lead['partner_id'] ? (int)$lead['partner_id'] : null,
    'partner' => $partner,
    'name' => $lead['name'],
    'email' => $lead['email'] ?? '',
    'phone' => $lead['phone'] ?? '',
    'message' => $lead['message'] ?? '',
    'service' => $lead['service'] ?? 'CIBIL Repair',
    'status' => $lead['status'] ?? 'new',
    'priority' => $lead['priority'] ?? 'medium',
    'source' => $lead['source'] ?? 'Website',
    'amount' => $lead['amount'] ? (float)$lead['amount'] : 0,
    'assigned_to' => $lead['assigned_to'] ? (int)$lead['assigned_to'] : null,
    'assigned_user' => $assignedUser,
    'notes' => $lead['notes'] ?? '',
    'created_at' => $lead['created_at'],
    'stats' => [
        'lead_age' => $leadAge . ' days',
        'has_converted' => $lead['status'] === 'converted'
    ],
    'recent_sales' => $sales,
    'recent_activities' => $activities
];

echo json_encode([
    'success' => true,
    'message' => 'Lead retrieved successfully',
    'data' => $formattedLead
]);

mysqli_close($conn);
exit;
?>