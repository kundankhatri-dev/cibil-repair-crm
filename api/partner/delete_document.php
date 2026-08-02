<?php
// api/partner/delete_document.php
// Partner Delete Document API - Delete uploaded documents

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

// ========== ENSURE DOCUMENTS TABLE EXISTS ==========
$documentsTable = 'partner_documents';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$documentsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS $documentsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        document_name VARCHAR(255) NOT NULL,
        document_type VARCHAR(50),
        file_path VARCHAR(500) NOT NULL,
        file_size INT,
        file_type VARCHAR(100),
        status ENUM('active', 'deleted') DEFAULT 'active',
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        INDEX idx_status (status)
    )";
    mysqli_query($conn, $createTable);
}

// ========== GET INPUT DATA ==========
$data = json_decode(file_get_contents('php://input'), true);
$document_id = isset($data['document_id']) ? (int)$data['document_id'] : 0;
$permanent_delete = isset($data['permanent_delete']) ? (bool)$data['permanent_delete'] : true;

if ($document_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid Document ID is required']);
    exit;
}

// ========== GET DOCUMENT INFO ==========
$query = "SELECT id, file_path, document_name, document_type, file_size, file_type FROM $documentsTable WHERE id = ? AND partner_id = ? AND status = 'active'";
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ii", $document_id, $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$document = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$document) {
    echo json_encode(['success' => false, 'error' => 'Document not found or access denied']);
    exit;
}

// ========== DELETE PHYSICAL FILE (Optional) ==========
$file_deleted = false;
$file_error = null;

if ($permanent_delete && !empty($document['file_path'])) {
    // Construct full file path
    $full_path = __DIR__ . '/../' . $document['file_path'];
    $full_path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $full_path);
    
    if (file_exists($full_path)) {
        if (unlink($full_path)) {
            $file_deleted = true;
        } else {
            $file_error = "Could not delete physical file (permission denied)";
        }
    } else {
        $file_error = "Physical file not found";
    }
}

// ========== SOFT DELETE FROM DATABASE ==========
if ($permanent_delete) {
    // Permanent delete from database
    $delete_stmt = mysqli_prepare($conn, "DELETE FROM $documentsTable WHERE id = ? AND partner_id = ?");
} else {
    // Soft delete (just mark as deleted)
    $delete_stmt = mysqli_prepare($conn, "UPDATE $documentsTable SET status = 'deleted' WHERE id = ? AND partner_id = ?");
}

if (!$delete_stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($delete_stmt, "ii", $document_id, $partner_id);

if (mysqli_stmt_execute($delete_stmt)) {
    $rows_affected = mysqli_stmt_affected_rows($delete_stmt);
    mysqli_stmt_close($delete_stmt);
    
    if ($rows_affected > 0) {
        // Log activity
        $checkActivityTable = mysqli_query($conn, "SHOW TABLES LIKE 'activities'");
        if (mysqli_num_rows($checkActivityTable) > 0) {
            $log_stmt = mysqli_prepare($conn, "INSERT INTO activities (user_id, activity_type, description, created_at) VALUES (?, 'delete_document', ?, NOW())");
            if ($log_stmt) {
                $delete_type = $permanent_delete ? 'permanently deleted' : 'deleted';
                $description = $delete_type . " document: " . $document['document_name'];
                if ($document['document_type']) {
                    $description .= " (" . $document['document_type'] . ")";
                }
                mysqli_stmt_bind_param($log_stmt, "is", $partner_id, $description);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            }
        }
        
        $response = [
            'success' => true,
            'message' => $permanent_delete ? 'Document permanently deleted' : 'Document moved to trash',
            'document_id' => $document_id,
            'document_name' => $document['document_name'],
            'permanent_delete' => $permanent_delete,
            'file_deleted' => $file_deleted
        ];
        
        if ($file_error) {
            $response['file_error'] = $file_error;
            $response['warning'] = 'Document removed from database but physical file may still exist.';
        }
        
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'error' => 'Document already deleted or not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete document: ' . mysqli_error($conn)]);
}

// ========== CLEAN UP ==========
if (isset($role_check)) mysqli_stmt_close($role_check);

mysqli_close($conn);
?>