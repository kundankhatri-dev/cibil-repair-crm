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
    
    // Stats
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM kyc_records GROUP BY status");
    $stats = ['pending' => 0, 'verified' => 0, 'rejected' => 0];
    while ($row = $stmt->fetch()) {
        $stats[$row['status']] = (int)$row['count'];
    }
    
    // Queue
    $stmt = $pdo->query("
        SELECT k.*, c.name as client_name 
        FROM kyc_records k 
        LEFT JOIN customers c ON k.client_id = c.id 
        ORDER BY k.created_at ASC
    ");
    $queue = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'pending' => $stats['pending'],
        'verified' => $stats['verified'],
        'rejected' => $stats['rejected'],
        'queue' => $queue
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>