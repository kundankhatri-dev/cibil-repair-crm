<?php
// api/lead/update_lead_stage.php - Update lead stage
session_start();
header('Content-Type: application/json');

$allowed_roles = ['sales', 'bd', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$lead_id = isset($input['lead_id']) ? (int)$input['lead_id'] : 0;
$stage = trim($input['stage'] ?? '');
$notes = trim($input['notes'] ?? '');

if ($lead_id <= 0 || empty($stage)) {
    echo json_encode(['success' => false, 'error' => 'Lead ID and stage required']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$query = "UPDATE leads SET stage = ?, notes = CONCAT(notes, '\n', NOW(), ': ', ?), updated_at = NOW() WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
$note_prefix = "Stage updated to: " . $stage;
$full_note = $notes ? $note_prefix . " - " . $notes : $note_prefix;
mysqli_stmt_bind_param($stmt, "ssi", $stage, $full_note, $lead_id);
$updated = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($updated) {
    echo json_encode(['success' => true, 'message' => 'Stage updated']);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}

mysqli_close($conn);
?>