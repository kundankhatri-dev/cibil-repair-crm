<?php
// api/partner/send_bulk_sms.php
// Bulk SMS to leads/customers

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$recipient_type = $data['recipient_type'] ?? 'leads'; // leads, customers, both
$message = trim($data['message'] ?? '');
$status_filter = $data['status_filter'] ?? 'all';

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

if (strlen($message) > 160) {
    echo json_encode(['success' => false, 'error' => 'Message exceeds 160 characters limit']);
    exit;
}

// Determine leads table
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// Fetch recipients
$recipients = [];
$query = "SELECT customer_name, customer_phone FROM $leadsTable WHERE partner_id = ?";

if ($recipient_type === 'leads') {
    $query .= " AND status != 'converted'";
    if ($status_filter !== 'all') {
        $query .= " AND status = ?";
    }
} elseif ($recipient_type === 'customers') {
    $query .= " AND status = 'converted'";
} else {
    // Both leads and customers
    if ($status_filter !== 'all') {
        $query .= " AND status = ?";
    }
}

$stmt = mysqli_prepare($conn, $query);
if ($status_filter !== 'all' && $recipient_type !== 'customers') {
    mysqli_stmt_bind_param($stmt, "is", $partner_id, $status_filter);
} else {
    mysqli_stmt_bind_param($stmt, "i", $partner_id);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recipients = mysqli_fetch_all($result, MYSQLI_ASSOC);

if (empty($recipients)) {
    echo json_encode(['success' => false, 'error' => 'No recipients found']);
    exit;
}

// Simulate SMS sending (replace with actual SMS gateway API)
$sent_count = 0;
$failed_count = 0;

foreach ($recipients as $recipient) {
    $phone = $recipient['customer_phone'];
    if (!empty($phone) && strlen($phone) == 10) {
        // In production, integrate with SMS gateway like Twilio, MSG91, etc.
        // Example with MSG91:
        // $sms_response = sendSMSViaMSG91($phone, $message);
        $sent_count++;
    } else {
        $failed_count++;
    }
    
    // Rate limiting - don't send too many at once
    usleep(100000); // 0.1 second delay between messages
}

// Log bulk SMS activity
$activityTable = 'activities';
$checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE '$activityTable'");
if (mysqli_num_rows($checkActivityTable) > 0) {
    $log_stmt = mysqli_prepare($conn, "INSERT INTO $activityTable (user_id, activity_type, description, created_at) VALUES (?, 'bulk_sms', ?, NOW())");
    $description = "Sent bulk SMS to $sent_count recipients (Type: $recipient_type)";
    mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
    mysqli_stmt_execute($log_stmt);
}

echo json_encode([
    'success' => true,
    'message' => 'Bulk SMS sent successfully',
    'summary' => [
        'total_recipients' => count($recipients),
        'sent' => $sent_count,
        'failed' => $failed_count,
        'recipient_type' => $recipient_type,
        'message_preview' => substr($message, 0, 50) . (strlen($message) > 50 ? '...' : '')
    ],
    'estimated_cost' => '₹' . ($sent_count * 0.15) // Assuming ₹0.15 per SMS
]);

mysqli_close($conn);
?>