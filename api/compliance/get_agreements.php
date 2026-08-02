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
    $status = $_GET['status'] ?? '';
    
    $sql = "SELECT a.*, c.name as client_name 
            FROM client_agreements a 
            LEFT JOIN customers c ON a.client_id = c.id 
            WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND (c.name LIKE ? OR a.agreement_no LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($status) {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $agreements = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'agreements' => $agreements]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>