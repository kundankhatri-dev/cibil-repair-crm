<?php
// ============================================================
// API: Partner Update Referral - WITH COMMISSION
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
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$phone = trim($data['phone'] ?? '');
$type = trim($data['type'] ?? 'partner');
$status = trim($data['status'] ?? 'registered');
$notes = trim($data['notes'] ?? '');

// ========== NEW: COMMISSION FIELDS ==========
$commission_rate = isset($data['commission_rate']) ? (float)$data['commission_rate'] : 10;
$earnings = isset($data['earnings']) ? (float)$data['earnings'] : 0;
// ============================================

// ========== VALIDATE ==========
if ($id <= 0 || empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
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

// ========== UPDATE REFERRAL ==========
// ========== ADD THE UPDATE QUERY HERE ==========
$update = "UPDATE partner_referrals SET 
    referred_name = '" . mysqli_real_escape_string($conn, $name) . "',
    referred_email = '" . mysqli_real_escape_string($conn, $email) . "',
    referred_phone = '" . mysqli_real_escape_string($conn, $phone) . "',
    type = '" . mysqli_real_escape_string($conn, $type) . "',
    status = '" . mysqli_real_escape_string($conn, $status) . "',
    commission_rate = $commission_rate,
    commission_earned = $earnings,
    notes = '" . mysqli_real_escape_string($conn, $notes) . "'
WHERE id = $id AND partner_id = $partner_id";
// ======================================================

if (mysqli_query($conn, $update)) {
    // If status is 'converted', update converted_at
    if ($status === 'converted') {
        mysqli_query($conn, "UPDATE partner_referrals SET converted_at = NOW() WHERE id = $id");
    }
    
    echo json_encode(['success' => true, 'message' => 'Referral updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update referral: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>