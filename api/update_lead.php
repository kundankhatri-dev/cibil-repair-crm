<?php
// ============================================================
// CIBIL REPAIR CRM - Update Lead API (FIXED)
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

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

// Get input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    mysqli_close($conn);
    exit;
}

$id = isset($input['id']) ? intval($input['id']) : 0;
$status = isset($input['status']) ? trim($input['status']) : '';
$priority = isset($input['priority']) ? trim($input['priority']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Lead ID required']);
    mysqli_close($conn);
    exit;
}

// Check if lead exists
$check = mysqli_query($conn, "SELECT id, name, status FROM leads WHERE id = $id");
if (mysqli_num_rows($check) == 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Lead not found']);
    mysqli_close($conn);
    exit;
}

$lead = mysqli_fetch_assoc($check);

// Build update - WITHOUT updated_at column
$updates = [];
if (!empty($status)) {
    $updates[] = "status = '$status'";
}
if (!empty($priority)) {
    $updates[] = "priority = '$priority'";
}
if (!empty($notes)) {
    $updates[] = "notes = '" . mysqli_real_escape_string($conn, $notes) . "'";
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'error' => 'No fields to update']);
    mysqli_close($conn);
    exit;
}

$query = "UPDATE leads SET " . implode(', ', $updates) . " WHERE id = $id";

if (mysqli_query($conn, $query)) {
    echo json_encode([
        'success' => true,
        'message' => 'Lead updated successfully',
        'data' => [
            'id' => $id,
            'name' => $lead['name'],
            'status' => $status ?: $lead['status']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>