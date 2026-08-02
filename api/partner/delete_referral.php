<?php
// ============================================================
// API: Partner Delete Referral
// ============================================================

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ========== GET INPUT ==========
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    $data = $_POST;
}

$id = isset($data['referral_id']) ? (int)$data['referral_id'] : 0;

// ========== VALIDATE ==========
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid referral ID']);
    exit;
}

// ========== CHECK IF REFERRAL EXISTS ==========
$checkQuery = "SELECT id FROM partner_referrals WHERE id = $id AND partner_id = $partner_id";
$checkResult = mysqli_query($conn, $checkQuery);
if (!$checkResult || mysqli_num_rows($checkResult) == 0) {
    echo json_encode(['success' => false, 'error' => 'Referral not found']);
    mysqli_close($conn);
    exit;
}

// ========== DELETE REFERRAL ==========
$delete = "DELETE FROM partner_referrals WHERE id = $id AND partner_id = $partner_id";

if (mysqli_query($conn, $delete)) {
    echo json_encode(['success' => true, 'message' => 'Referral deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete referral: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>