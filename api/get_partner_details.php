<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin', 'partner'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$partner_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$partner_id) {
    echo json_encode(['success' => false, 'error' => 'Partner ID required']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get partner details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'partner'");
$stmt->execute([$partner_id]);
$partner = $stmt->fetch();

if (!$partner) {
    echo json_encode(['success' => false, 'error' => 'Partner not found']);
    exit;
}

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total_leads FROM leads WHERE partner_id = ?");
$stmt->execute([$partner_id]);
$leads = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) as converted_leads FROM leads WHERE partner_id = ? AND status = 'converted'");
$stmt->execute([$partner_id]);
$converted = $stmt->fetch();

$stmt = $pdo->prepare("SELECT SUM(amount) as total_commission FROM commission WHERE partner_id = ? AND status = 'earned'");
$stmt->execute([$partner_id]);
$commission = $stmt->fetch();

echo json_encode([
    'success' => true,
    'partner' => $partner,
    'stats' => [
        'total_leads' => (int)$leads['total_leads'],
        'converted_leads' => (int)$converted['converted_leads'],
        'total_commission' => (float)$commission['total_commission'] ?? 0
    ]
]);
?>