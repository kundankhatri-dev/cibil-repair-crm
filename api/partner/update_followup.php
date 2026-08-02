<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$followup_id = $data['followup_id'] ?? 0;
$status = $data['status'] ?? 'completed';

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$query = "UPDATE partner_followups SET status = ?, completed_at = NOW() 
          WHERE id = ? AND partner_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sii", $status, $followup_id, $partner_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}

mysqli_close($conn);
?>