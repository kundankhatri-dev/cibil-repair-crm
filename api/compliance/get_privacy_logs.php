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
    
    $stmt = $pdo->query("
        SELECT p.*, c.name as client_name, u.name as user_name 
        FROM privacy_logs p 
        LEFT JOIN customers c ON p.client_id = c.id 
        LEFT JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
        LIMIT 50
    ");
    $logs = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'logs' => $logs]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>