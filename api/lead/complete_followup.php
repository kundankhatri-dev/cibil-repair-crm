<?php
// api/lead/complete_followup.php - Mark follow-up as completed
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

$followup_id = isset($input['followup_id']) ? (int)$input['followup_id'] : 0;

if ($followup_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Follow-up ID required']);
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

$query = "UPDATE lead_followups SET status = 'completed', completed_at = NOW() WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $followup_id);
$updated = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($updated) {
    echo json_encode(['success' => true, 'message' => 'Follow-up completed']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update']);
}

mysqli_close($conn);
?>