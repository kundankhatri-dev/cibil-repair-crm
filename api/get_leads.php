<?php
// ============================================================
// GET PARTNER LEADS - API
// ============================================================
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$isAdmin = in_array($_SESSION['user_role'], ['admin', 'super_admin']);
$partner_id = isset($_GET['partner_id']) ? (int)$_GET['partner_id'] : 0;

// If admin view, allow viewing any partner's data
if ($isAdmin && $partner_id > 0) {
    $viewing_partner_id = $partner_id;
} else {
    // Regular partner can only view their own data
    if ($_SESSION['user_role'] !== 'partner') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $viewing_partner_id = (int)$_SESSION['user_id'];
}

// Database connection
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

try {
    // Get leads for the partner
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE partner_id = ? ORDER BY created_at DESC");
    $stmt->execute([$viewing_partner_id]);
    $leads = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'leads' => $leads,
        'total' => count($leads)
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>