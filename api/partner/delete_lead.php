<?php
// api/partner/delete_lead.php
// Partner Delete Lead API - Delete a lead (with related data cleanup)

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

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);
$lead_id = isset($data['lead_id']) ? (int)$data['lead_id'] : 0;
$soft_delete = isset($data['soft_delete']) ? (bool)$data['soft_delete'] : false;

if ($lead_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid Lead ID is required']);
    exit;
}

// ========== VERIFY LEAD BELONGS TO PARTNER ==========
$check_stmt = mysqli_prepare($conn, "SELECT id, customer_name, customer_phone, status, created_at FROM $leadsTable WHERE id = ? AND partner_id = ?");
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

// ========== RESTRICTIONS (Configurable) ==========
// Prevent deleting converted leads
if ($lead['status'] === 'converted') {
    echo json_encode(['success' => false, 'error' => 'Cannot delete converted leads. Converted leads are customer records.']);
    exit;
}

// Prevent deleting leads older than 30 days (optional)
$max_age_days = 30;
$lead_age = (time() - strtotime($lead['created_at'])) / (60 * 60 * 24);
if ($lead_age > $max_age_days && !$soft_delete) {
    echo json_encode([
        'success' => false, 
        'error' => "Cannot delete leads older than $max_age_days days. Lead is " . round($lead_age) . " days old."
    ]);
    exit;
}

// ========== START TRANSACTION ==========
mysqli_begin_transaction($conn);

$deleted_related = [];
$errors = [];

// ========== DELETE RELATED DATA ==========

// 1. Delete follow-ups
$followupsTable = 'partner_lead_followups';
$checkFollowupsTable = mysqli_query($conn, "SHOW TABLES LIKE '$followupsTable'");
if (mysqli_num_rows($checkFollowupsTable) > 0) {
    $delete_followups = mysqli_prepare($conn, "DELETE FROM $followupsTable WHERE lead_id = ?");
    if ($delete_followups) {
        mysqli_stmt_bind_param($delete_followups, "i", $lead_id);
        if (mysqli_stmt_execute($delete_followups)) {
            $deleted_related['followups'] = mysqli_stmt_affected_rows($delete_followups);
        }
        mysqli_stmt_close($delete_followups);
    }
}

// 2. Delete documents associated with this lead
$documentsTable = 'partner_documents';
$checkDocsTable = mysqli_query($conn, "SHOW TABLES LIKE '$documentsTable'");
if (mysqli_num_rows($checkDocsTable) > 0) {
    // Get document paths first to delete physical files
    $get_docs = mysqli_prepare($conn, "SELECT file_path FROM $documentsTable WHERE lead_id = ? AND partner_id = ?");
    if ($get_docs) {
        mysqli_stmt_bind_param($get_docs, "ii", $lead_id, $partner_id);
        mysqli_stmt_execute($get_docs);
        $docs_result = mysqli_stmt_get_result($get_docs);
        while ($doc = mysqli_fetch_assoc($docs_result)) {
            if (!empty($doc['file_path'])) {
                $full_path = __DIR__ . '/../' . $doc['file_path'];
                if (file_exists($full_path)) {
                    @unlink($full_path);
                }
            }
        }
        mysqli_stmt_close($get_docs);
    }
    
    $delete_docs = mysqli_prepare($conn, "DELETE FROM $documentsTable WHERE lead_id = ? AND partner_id = ?");
    if ($delete_docs) {
        mysqli_stmt_bind_param($delete_docs, "ii", $lead_id, $partner_id);
        if (mysqli_stmt_execute($delete_docs)) {
            $deleted_related['documents'] = mysqli_stmt_affected_rows($delete_docs);
        }
        mysqli_stmt_close($delete_docs);
    }
}

// 3. Delete lead notes (if stored separately)
$notesTable = 'lead_notes';
$checkNotesTable = mysqli_query($conn, "SHOW TABLES LIKE '$notesTable'");
if (mysqli_num_rows($checkNotesTable) > 0) {
    $delete_notes = mysqli_prepare($conn, "DELETE FROM $notesTable WHERE lead_id = ?");
    if ($delete_notes) {
        mysqli_stmt_bind_param($delete_notes, "i", $lead_id);
        mysqli_stmt_execute($delete_notes);
        mysqli_stmt_close($delete_notes);
    }
}

// ========== DELETE LEAD ==========
if ($soft_delete && checkColumnExists($conn, $leadsTable, 'status')) {
    // Soft delete - just mark as deleted
    $delete_stmt = mysqli_prepare($conn, "UPDATE $leadsTable SET status = 'deleted', deleted_at = NOW() WHERE id = ? AND partner_id = ?");
    $delete_type = 'soft';
    $success_message = 'Lead moved to trash';
} else {
    // Permanent delete
    $delete_stmt = mysqli_prepare($conn, "DELETE FROM $leadsTable WHERE id = ? AND partner_id = ?");
    $delete_type = 'permanent';
    $success_message = 'Lead permanently deleted';
}

if (!$delete_stmt) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($delete_stmt, "ii", $lead_id, $partner_id);

if (mysqli_stmt_execute($delete_stmt)) {
    $rows_affected = mysqli_stmt_affected_rows($delete_stmt);
    mysqli_stmt_close($delete_stmt);
    
    if ($rows_affected > 0) {
        // Update partner stats (decrement total leads)
        $update_partner = mysqli_prepare($conn, "UPDATE partners SET total_leads = total_leads - 1 WHERE user_id = ?");
        if ($update_partner) {
            mysqli_stmt_bind_param($update_partner, "i", $partner_id);
            mysqli_stmt_execute($update_partner);
            mysqli_stmt_close($update_partner);
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Log activity
        $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
        if (mysqli_num_rows($checkActivityTable) > 0) {
            $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'delete_lead', ?, NOW())");
            if ($log_stmt) {
                $description = $delete_type . " delete of lead: " . $lead['customer_name'] . " (Phone: " . $lead['customer_phone'] . ")";
                mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => $success_message,
            'delete_type' => $delete_type,
            'lead_id' => $lead_id,
            'lead_name' => $lead['customer_name'],
            'deleted_related' => $deleted_related
        ]);
    } else {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'error' => 'Lead not found or already deleted']);
    }
} else {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => 'Failed to delete lead: ' . mysqli_error($conn)]);
}

// ========== HELPER FUNCTION ==========
function checkColumnExists($conn, $table, $column) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '$column'");
    return mysqli_num_rows($check) > 0;
}

// ========== CLEAN UP ==========
if (isset($role_check)) mysqli_stmt_close($role_check);

mysqli_close($conn);
?>