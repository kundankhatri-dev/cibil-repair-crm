<?php
// api/followup/create.php
require_once 'config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['lead_id'])) {
    echo json_encode(['success' => false, 'error' => 'Lead ID is required']);
    exit;
}

if (empty($data['followup_date'])) {
    echo json_encode(['success' => false, 'error' => 'Follow-up date is required']);
    exit;
}

// Verify lead belongs to partner
$check_lead = mysqli_prepare($conn, "SELECT id, customer_name, service_type FROM partner_leads WHERE id = ? AND partner_id = ?");
mysqli_stmt_bind_param($check_lead, "ii", $data['lead_id'], $partner_id);
mysqli_stmt_execute($check_lead);
$lead_result = mysqli_stmt_get_result($check_lead);
$lead = mysqli_fetch_assoc($lead_result);

if (!$lead) {
    echo json_encode(['success' => false, 'error' => 'Lead not found or access denied']);
    exit;
}

// Prepare data
$title = !empty($data['title']) ? $data['title'] : "Follow-up for " . $lead['customer_name'];
$description = $data['description'] ?? '';
$priority = $data['priority'] ?? 'medium';
$followup_date = date('Y-m-d H:i:s', strtotime($data['followup_date']));

// Validate priority
$valid_priorities = ['low', 'medium', 'high', 'urgent'];
if (!in_array($priority, $valid_priorities)) {
    $priority = 'medium';
}

// Insert follow-up
$query = "INSERT INTO followups (lead_id, partner_id, followup_date, title, description, priority, status, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iissss", $data['lead_id'], $partner_id, $followup_date, $title, $description, $priority);

if (mysqli_stmt_execute($stmt)) {
    $followup_id = mysqli_insert_id($conn);
    
    // Update lead's next_followup date
    $update_lead = mysqli_prepare($conn, "UPDATE partner_leads SET next_followup = ?, followup_count = followup_count + 1 WHERE id = ?");
    mysqli_stmt_bind_param($update_lead, "si", $followup_date, $data['lead_id']);
    mysqli_stmt_execute($update_lead);
    
    echo json_encode([
        'success' => true,
        'message' => 'Follow-up created successfully',
        'followup_id' => $followup_id,
        'followup' => [
            'id' => $followup_id,
            'lead_id' => $data['lead_id'],
            'customer_name' => $lead['customer_name'],
            'title' => $title,
            'followup_date' => $followup_date,
            'priority' => $priority,
            'status' => 'pending'
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to create follow-up: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>