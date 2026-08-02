<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['ceo', 'founder', 'admin', 'director'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

$query = "SELECT u.id, u.name,
          (SELECT COUNT(*) FROM leads WHERE partner_id = u.id) as leads_sent,
          (SELECT COUNT(*) FROM leads WHERE partner_id = u.id AND status='converted') as conversions,
          (SELECT SUM(commission) FROM partner_commission WHERE partner_id = u.id) as commission,
          (SELECT commission_rate FROM partner_commission WHERE partner_id = u.id LIMIT 1) as tier
          FROM users u
          WHERE u.role = 'partner' AND u.status = 'active'
          ORDER BY conversions DESC";
$result = mysqli_query($conn, $query);
$partners = [];
while ($row = mysqli_fetch_assoc($result)) {
    $conv_rate = $row['leads_sent'] > 0 ? round(($row['conversions'] / $row['leads_sent']) * 100) : 0;
    $tier_name = $row['tier'] >= 50 ? 'Diamond' : ($row['tier'] >= 30 ? 'Gold' : ($row['tier'] >= 15 ? 'Silver' : 'Bronze'));
    $partners[] = [
        'name' => $row['name'], 'leads_sent' => (int)$row['leads_sent'],
        'conversions' => (int)$row['conversions'], 'conversion_rate' => $conv_rate,
        'commission' => (float)($row['commission'] ?? 0), 'payout_status' => 'pending',
        'tier' => $tier_name
    ];
}

echo json_encode(['success' => true, 'partners' => $partners]);
mysqli_close($conn);
?>