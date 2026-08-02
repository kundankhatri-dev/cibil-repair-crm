<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['compliance_team', 'legal_team', 'admin', 'manager', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $search = $_GET['search'] ?? '';
    $action = $_GET['action'] ?? '';
    
    $sql = "SELECT * FROM activity_log WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (details LIKE ? OR user_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($action) {
        $sql .= " AND action LIKE ?";
        $params[] = "%$action%";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'logs' => $logs]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>