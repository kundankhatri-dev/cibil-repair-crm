<?php
// ============================================================
// API: Partner Dashboard - FIXED
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.php']);
    exit;
}

$partner_id = (int)$_SESSION['user_id'];

// ========== DIRECT DATABASE CONNECTION ==========
$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// Verify partner role
$result = mysqli_query($conn, "SELECT role FROM users WHERE id = $partner_id");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    if (!$row || $row['role'] !== 'partner') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
        mysqli_close($conn);
        exit;
    }
}

// ========== GET STATS DIRECTLY ==========

// 1. Total Leads
$total_leads = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM leads WHERE partner_id = $partner_id");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_leads = (int)($row['cnt'] ?? 0);
}

// 2. Converted Leads
$converted = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM leads WHERE partner_id = $partner_id AND status = 'converted'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $converted = (int)($row['cnt'] ?? 0);
}

// 3. Total Commission
$commission = 0;
$result = mysqli_query($conn, "SELECT SUM(amount) as total FROM leads WHERE partner_id = $partner_id AND status = 'converted'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $commission = (float)($row['total'] ?? 0);
}

// 4. New Leads
$new_leads = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM leads WHERE partner_id = $partner_id AND status = 'new'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $new_leads = (int)($row['cnt'] ?? 0);
}

// 5. Contacted Leads
$contacted_leads = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM leads WHERE partner_id = $partner_id AND status = 'contacted'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $contacted_leads = (int)($row['cnt'] ?? 0);
}

// 6. Lost Leads
$lost_leads = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM leads WHERE partner_id = $partner_id AND status = 'lost'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $lost_leads = (int)($row['cnt'] ?? 0);
}

// 7. Conversion Rate
$rate = $total_leads > 0 ? round(($converted / $total_leads) * 100) : 0;

// ========== SEND RESPONSE ==========
echo json_encode([
    'success' => true,
    'total_leads' => $total_leads,
    'converted_customers' => $converted,
    'converted' => $converted,
    'total_commission' => $commission,
    'pending_payout' => 0,
    'followups_due' => 0,
    'conversion_rate' => $rate,
    'new_leads' => $new_leads,
    'contacted_leads' => $contacted_leads,
    'lost_leads' => $lost_leads,
    'recent_activity' => [],
    'monthly_commission' => []
]);

mysqli_close($conn);
?>