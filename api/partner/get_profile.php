<?php
// ============================================================
// API: Partner Get Profile
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];

// ========== DATABASE CONNECTION ==========
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ========== GET USER DATA ==========
$query = "SELECT id, name, email, phone, created_at FROM users WHERE id = $partner_id";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Query failed: ' . mysqli_error($conn)]);
    mysqli_close($conn);
    exit;
}

$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    mysqli_close($conn);
    exit;
}

// ========== GET PARTNER DATA ==========
$partnerQuery = "SELECT * FROM partners WHERE user_id = $partner_id";
$partnerResult = mysqli_query($conn, $partnerQuery);
$partnerData = mysqli_fetch_assoc($partnerResult);

// ========== BUILD PROFILE ==========
$profile = [
    'id' => (int)$user['id'],
    'name' => $user['name'] ?? '',
    'email' => $user['email'] ?? '',
    'phone' => $user['phone'] ?? '',
    'company_name' => $partnerData['company_name'] ?? '',
    'city' => $partnerData['city'] ?? '',
    'bank_name' => $partnerData['bank_name'] ?? '',
    'account_number' => $partnerData['account_number'] ?? '',
    'ifsc_code' => $partnerData['ifsc_code'] ?? '',
    'account_holder' => $partnerData['account_holder_name'] ?? '',
    'joined' => $user['created_at'] ?? ''
];

echo json_encode([
    'success' => true,
    'profile' => $profile
]);

mysqli_close($conn);
?>