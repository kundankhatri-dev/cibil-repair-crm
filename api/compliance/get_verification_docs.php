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
        SELECT d.*, c.name as client_name 
        FROM verification_docs d 
        LEFT JOIN customers c ON d.client_id = c.id 
        ORDER BY d.uploaded_at DESC
    ");
    $documents = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'documents' => $documents]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>