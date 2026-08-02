<?php
// api/partner/update-status.php - Update partner status (Admin only)

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database connection
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['user_id'] ?? 0;
$status = $data['status'] ?? '';

$allowedStatuses = ['active', 'inactive', 'rejected', 'approved'];
if (!$userId || !in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Update user status
$stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE id = ? AND role = 'partner'");
mysqli_stmt_bind_param($stmt, "si", $status, $userId);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    // Get user email for notification
    $emailStmt = mysqli_prepare($conn, "SELECT name, email FROM users WHERE id = ?");
    mysqli_stmt_bind_param($emailStmt, "i", $userId);
    mysqli_stmt_execute($emailStmt);
    $result = mysqli_stmt_get_result($emailStmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($status == 'active' || $status == 'approved') {
        $subject = "Partner Application Approved - CIBIL Repair";
        $message = "Dear " . $user['name'] . ",\n\n";
        $message .= "Congratulations! Your partner application has been APPROVED.\n\n";
        $message .= "You can now login to your partner dashboard using:\n";
        $message .= "Login URL: https://cibilrepair.in/login.html\n";
        $message .= "Email: " . $user['email'] . "\n\n";
        $message .= "Start referring clients and earn commissions!\n\n";
        $message .= "Thanks,\nCIBIL Repair Team";
        @mail($user['email'], $subject, $message);
    } elseif ($status == 'rejected') {
        $subject = "Partner Application Update - CIBIL Repair";
        $message = "Dear " . $user['name'] . ",\n\n";
        $message .= "Thank you for your interest. Unfortunately, your partner application has been REJECTED.\n\n";
        $message .= "Please contact support for more information.\n\n";
        $message .= "Thanks,\nCIBIL Repair Team";
        @mail($user['email'], $subject, $message);
    }
    
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'No changes made or user not found']);
}

mysqli_close($conn);
?>