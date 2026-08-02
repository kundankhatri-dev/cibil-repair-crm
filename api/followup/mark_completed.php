<?php
// api/followup/mark_completed.php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['followup_id'])) {
    echo json_encode(['success' => false, 'error' => 'Follow-up ID required']);
    exit;
}

$notes = isset($data['notes']) ? $data['notes'] : '';

$query = "UPDATE followups SET status = 'completed', completed_at = NOW(), notes = CONCAT(notes, '\n[Completed] ', ?) 
          WHERE id = ? AND partner_id = ? AND status != 'completed'";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sii", $notes, $data['followup_id'], $partner_id);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Follow-up marked as completed'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Follow-up not found or already completed']);
}

mysqli_close($conn);
?>