<?php
// api/reports/schedule.php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$template_id = (int)($data['template_id'] ?? 0);
$schedule_type = $data['schedule_type'] ?? 'weekly';
$recipient_email = trim($data['recipient_email'] ?? '');
$format = $data['format'] ?? 'pdf';

if (!$template_id || empty($recipient_email)) {
    echo json_encode(['success' => false, 'error' => 'Template ID and recipient email required']);
    exit;
}

// Calculate next send date
$next_send_at = date('Y-m-d H:i:s', strtotime('+1 day'));

$query = "INSERT INTO scheduled_reports (partner_id, template_id, schedule_type, recipient_email, format, next_send_at) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iissss", $partner_id, $template_id, $schedule_type, $recipient_email, $format, $next_send_at);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Report scheduled successfully',
        'schedule_id' => mysqli_insert_id($conn)
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
?>