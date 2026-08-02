<?php
// api/partner/request_deletion.php
// Request account deletion (GDPR Right to Erasure)

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$reason = $data['reason'] ?? '';

// Create deletion requests table
$deletionTable = 'deletion_requests';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$deletionTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE $deletionTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reason TEXT,
        status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        notes TEXT,
        INDEX idx_user (user_id)
    )";
    mysqli_query($conn, $createTable);
}

// Check if already requested
$check = mysqli_prepare($conn, "SELECT id, status FROM $deletionTable WHERE user_id = ? AND status IN ('pending', 'processing')");
mysqli_stmt_bind_param($check, "i", $partner_id);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);
$existing = mysqli_fetch_assoc($check);

if ($existing) {
    echo json_encode([
        'success' => false,
        'error' => 'Deletion request already submitted and is ' . $existing['status']
    ]);
    exit;
}

// Insert deletion request
$insert = mysqli_prepare($conn, "INSERT INTO $deletionTable (user_id, reason) VALUES (?, ?)");
mysqli_stmt_bind_param($insert, "is", $partner_id, $reason);

if (mysqli_stmt_execute($insert)) {
    // Log out user immediately
    session_destroy();
    
    echo json_encode([
        'success' => true,
        'message' => 'Your account deletion request has been submitted. You will be notified once processed (typically within 30 days).',
        'reference_id' => mysqli_insert_id($conn),
        'gdpr_compliant' => true,
        'retention_period_days' => 30
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to submit request']);
}

mysqli_close($conn);
?>