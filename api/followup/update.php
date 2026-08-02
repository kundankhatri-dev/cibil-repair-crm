<?php
// api/followup/update.php
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

// Verify ownership
$check = mysqli_prepare($conn, "SELECT id FROM followups WHERE id = ? AND partner_id = ?");
mysqli_stmt_bind_param($check, "ii", $data['followup_id'], $partner_id);
mysqli_stmt_execute($check);
if (mysqli_stmt_num_rows($check) == 0) {
    echo json_encode(['success' => false, 'error' => 'Follow-up not found']);
    exit;
}

$updates = [];
$params = [];
$types = "";

if (isset($data['status'])) {
    $updates[] = "status = ?";
    $params[] = $data['status'];
    $types .= "s";
    
    if ($data['status'] == 'completed') {
        $updates[] = "completed_at = NOW()";
    }
}

if (isset($data['followup_date'])) {
    $updates[] = "followup_date = ?";
    $params[] = date('Y-m-d H:i:s', strtotime($data['followup_date']));
    $types .= "s";
}

if (isset($data['title'])) {
    $updates[] = "title = ?";
    $params[] = $data['title'];
    $types .= "s";
}

if (isset($data['description'])) {
    $updates[] = "description = ?";
    $params[] = $data['description'];
    $types .= "s";
}

if (isset($data['priority'])) {
    $updates[] = "priority = ?";
    $params[] = $data['priority'];
    $types .= "s";
}

if (isset($data['notes'])) {
    $updates[] = "notes = ?";
    $params[] = $data['notes'];
    $types .= "s";
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'error' => 'No fields to update']);
    exit;
}

$query = "UPDATE followups SET " . implode(", ", $updates) . " WHERE id = ? AND partner_id = ?";
$params[] = $data['followup_id'];
$params[] = $partner_id;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Follow-up updated successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>