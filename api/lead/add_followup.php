<?php
// api/lead/add_followup.php - Schedule a follow-up
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
$followup_date = trim($input['followup_date'] ?? '');
$notes = trim($input['notes'] ?? '');

if ($lead_id <= 0 || empty($followup_date)) {
    echo json_encode(['success' => false, 'error' => 'Lead ID and follow-up date required']);
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

$query = "INSERT INTO lead_followups (lead_id, followup_date, notes) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iss", $lead_id, $followup_date, $notes);
$inserted = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($inserted) {
    echo json_encode(['success' => true, 'message' => 'Follow-up scheduled']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to schedule follow-up']);
}

mysqli_close($conn);
?>