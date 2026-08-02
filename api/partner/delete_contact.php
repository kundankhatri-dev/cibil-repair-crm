<?php
// ============================================================
// API: Delete Contact
// ============================================================

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$contact_id = $data['contact_id'] ?? 0;

if (empty($contact_id)) {
    echo json_encode(['success' => false, 'error' => 'Contact ID is required']);
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

$query = "DELETE FROM contacts WHERE id = ? AND partner_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $contact_id, $partner_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Contact deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete contact: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>