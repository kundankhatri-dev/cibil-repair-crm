<?php
// api/partner/add_note.php
// Partner Add Note API - Add notes/remarks to a lead

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role, name FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// ========== DETERMINE LEADS TABLE ==========
$leadsTable = 'partner_leads';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$leadsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $leadsTable = 'leads';
}

// Check if notes column exists, if not add it
$checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM $leadsTable LIKE 'notes'");
if (mysqli_num_rows($checkColumn) == 0) {
    $alterTable = "ALTER TABLE $leadsTable ADD COLUMN notes TEXT DEFAULT NULL";
    mysqli_query($conn, $alterTable);
}

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);
$lead_id = isset($data['lead_id']) ? (int)$data['lead_id'] : 0;
$note = trim($data['note'] ?? '');

// ========== VALIDATE INPUT ==========
if ($lead_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid Lead ID is required']);
    exit;
}

if (empty($note)) {
    echo json_encode(['success' => false, 'error' => 'Note content is required']);
    exit;
}

// Validate note length
$note_length = strlen($note);
if ($note_length < 3) {
    echo json_encode(['success' => false, 'error' => 'Note must be at least 3 characters']);
    exit;
}

if ($note_length > 2000) {
    echo json_encode(['success' => false, 'error' => 'Note is too long (maximum 2000 characters)']);
    exit;
}

// ========== VERIFY LEAD BELONGS TO PARTNER ==========
$check_stmt = mysqli_prepare($conn, "SELECT id, customer_name, customer_phone, notes FROM $leadsTable WHERE id = ? AND partner_id = ?");
if (!$check_stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($check_stmt, "ii", $lead_id, $partner_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$lead = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

if (!$lead) {
    echo json_encode(['success' => false, 'error' => 'Lead not found or access denied']);
    exit;
}

// ========== ADD NEW NOTE ==========
$timestamp = date('d-m-Y H:i:s');
$new_note_line = "[$timestamp] " . $note . "\n";
$existing_notes = $lead['notes'] ?? '';
$updated_notes = $existing_notes . $new_note_line;

$update_stmt = mysqli_prepare($conn, "UPDATE $leadsTable SET notes = ?, updated_at = NOW() WHERE id = ?");
if (!$update_stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($update_stmt, "si", $updated_notes, $lead_id);

if (mysqli_stmt_execute($update_stmt)) {
    // Parse notes into array for response
    $notes_array = [];
    $notes_lines = explode("\n", trim($updated_notes));
    foreach ($notes_lines as $line) {
        if (preg_match('/\[(.*?)\]\s*(.*)/', $line, $matches)) {
            $notes_array[] = [
                'timestamp' => $matches[1],
                'note' => trim($matches[2])
            ];
        } elseif (!empty(trim($line))) {
            $notes_array[] = [
                'timestamp' => date('d-m-Y H:i:s'),
                'note' => trim($line)
            ];
        }
    }
    $notes_array = array_reverse($notes_array); // Show newest first
    
    // Log activity
    $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
    if (mysqli_num_rows($checkActivityTable) > 0) {
        $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'add_note', ?, NOW())");
        if ($log_stmt) {
            $short_note = strlen($note) > 50 ? substr($note, 0, 50) . '...' : $note;
            $description = "Added note for lead: " . $lead['customer_name'] . " - " . $short_note;
            mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Note added successfully',
        'lead_id' => $lead_id,
        'lead_name' => $lead['customer_name'],
        'added_note' => [
            'timestamp' => $timestamp,
            'content' => $note
        ],
        'all_notes' => $notes_array,
        'total_notes' => count($notes_array)
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to add note: ' . mysqli_error($conn)]);
}

// ========== CLEAN UP ==========
if (isset($update_stmt)) mysqli_stmt_close($update_stmt);
if (isset($role_check)) mysqli_stmt_close($role_check);

mysqli_close($conn);
?>