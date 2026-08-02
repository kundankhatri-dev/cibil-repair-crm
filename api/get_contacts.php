<?php
// ============================================================
// GET PARTNER CONTACTS - API
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
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

try {
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $sql = "SELECT * FROM contacts WHERE partner_id = ?";
    $params = [$viewing_partner_id];
    
    if ($category && $category !== 'all') {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $contacts,
        'total' => count($contacts)
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>