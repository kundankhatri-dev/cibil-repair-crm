<?php
// api/client/delete_document.php - Delete a document (soft delete)
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get client_id
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';

// Only client can delete their own documents
if ($viewer_role !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Only clients can delete their own documents']);
    exit;
}

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

$document_id = isset($input['document_id']) ? (int)$input['document_id'] : 0;
$permanent = isset($input['permanent']) ? (bool)$input['permanent'] : false;

if ($document_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid document ID']);
    exit;
}

// ========== CHECK IF DOCUMENT EXISTS AND BELONGS TO CLIENT ==========
$check_query = "SELECT id, document_name, file_path, status, is_deleted FROM client_documents WHERE id = ? AND client_id = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, "ii", $document_id, $client_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$document = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

if (!$document) {
    echo json_encode(['success' => false, 'error' => 'Document not found or access denied']);
    exit;
}

// Check if already deleted
if ($document['is_deleted'] == 1) {
    echo json_encode(['success' => false, 'error' => 'Document is already deleted']);
    exit;
}

// ========== CHECK IF DOCUMENT CAN BE DELETED ==========
// Verified documents cannot be deleted (for audit purposes)
if ($document['status'] === 'verified' && !$permanent) {
    echo json_encode(['success' => false, 'error' => 'Verified documents cannot be deleted. Please contact support if you need to remove this document.']);
    exit;
}

// ========== PERFORM SOFT DELETE OR PERMANENT DELETE ==========
if ($permanent) {
    // Permanent delete - remove file from server and database
    $file_path = $document['file_path'];
    $full_path = '../' . $file_path;
    
    // Delete physical file if exists
    if (file_exists($full_path)) {
        unlink($full_path);
    }
    
    // Delete from database
    $delete_query = "DELETE FROM client_documents WHERE id = ? AND client_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "ii", $document_id, $client_id);
    $deleted = mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);
    
    if ($deleted) {
        // Also delete from verification logs
        $delete_logs = mysqli_prepare($conn, "DELETE FROM document_verification_logs WHERE document_id = ?");
        mysqli_stmt_bind_param($delete_logs, "i", $document_id);
        mysqli_stmt_execute($delete_logs);
        mysqli_stmt_close($delete_logs);
        
        $message = "Document permanently deleted";
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete document']);
        exit;
    }
} else {
    // Soft delete - just mark as deleted
    $soft_delete_query = "UPDATE client_documents SET is_deleted = 1, deleted_at = NOW() WHERE id = ? AND client_id = ?";
    $soft_stmt = mysqli_prepare($conn, $soft_delete_query);
    mysqli_stmt_bind_param($soft_stmt, "ii", $document_id, $client_id);
    $deleted = mysqli_stmt_execute($soft_stmt);
    mysqli_stmt_close($soft_stmt);
    
    if ($deleted) {
        $message = "Document moved to trash";
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete document']);
        exit;
    }
}

// ========== ADD TO VERIFICATION LOG ==========
$log_note = $permanent ? "Document permanently deleted" : "Document soft deleted (moved to trash)";
$add_log = mysqli_prepare($conn, "INSERT INTO document_verification_logs (document_id, action, notes, performed_by) VALUES (?, 'deleted', ?, ?)");
mysqli_stmt_bind_param($add_log, "isi", $document_id, $log_note, $client_id);
mysqli_stmt_execute($add_log);
mysqli_stmt_close($add_log);

// ========== CREATE NOTIFICATION ==========
$notification_title = "Document Deleted";
$notification_message = "Your document '{$document['document_name']}' has been " . ($permanent ? "permanently deleted" : "moved to trash");

$add_notification = mysqli_prepare($conn, "INSERT INTO client_notifications (client_id, notification_type, title, message, link, priority) VALUES (?, 'document', ?, ?, ?, 'low')");
$link = "client-dashboard.php?section=documents";
mysqli_stmt_bind_param($add_notification, "issss", $client_id, $notification_title, $notification_message, $link);
mysqli_stmt_execute($add_notification);
mysqli_stmt_close($add_notification);

// ========== LOG ACTIVITY ==========
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$log_activity = mysqli_prepare($conn, "INSERT INTO client_activity_log (client_id, activity_type, description, ip_address, user_agent) VALUES (?, 'document_deleted', ?, ?, ?)");
$desc = "Deleted document: {$document['document_name']} (" . ($permanent ? "permanent" : "soft") . " delete)";
mysqli_stmt_bind_param($log_activity, "isss", $client_id, $desc, $ip_address, $user_agent);
mysqli_stmt_execute($log_activity);
mysqli_stmt_close($log_activity);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'message' => $message,
    'document' => [
        'id' => $document_id,
        'name' => $document['document_name'],
        'permanent' => $permanent
    ]
]);

mysqli_close($conn);
?>