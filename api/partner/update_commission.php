<?php
// api/partner/update_commission.php
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Get partner's current tier commission rate
$query = "SELECT commission_rate FROM partner_tiers WHERE partner_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $partner_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tier = mysqli_fetch_assoc($result);
$commission_rate = $tier ? $tier['commission_rate'] : 30;

// Update leads with commission based on current tier
$update = mysqli_prepare($conn, "UPDATE partner_leads SET commission_amount = (service_amount * ? / 100) WHERE partner_id = ? AND status = 'converted' AND commission_amount = 0");
mysqli_stmt_bind_param($update, "di", $commission_rate, $partner_id);
mysqli_stmt_execute($update);

echo json_encode([
    'success' => true,
    'commission_rate' => $commission_rate,
    'message' => "Commission rate updated to {$commission_rate}% based on your tier"
]);

mysqli_close($conn);
?>