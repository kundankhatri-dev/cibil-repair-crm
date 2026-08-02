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
    
    $input = json_decode(file_get_contents('php://input'), true);
    $client_id = (int)($input['client_id'] ?? 0);
    $status = $input['status'] ?? '';
    $remarks = $input['remarks'] ?? '';
    $user_id = (int)$_SESSION['user_id'];
    
    if (!$client_id || !$status) {
        echo json_encode(['success' => false, 'error' => 'Client and status are required']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        UPDATE kyc_records 
        SET status = ?, verification_remarks = ?, verified_by = ?, verified_at = NOW() 
        WHERE client_id = ?
    ");
    $stmt->execute([$status, $remarks, $user_id, $client_id]);
    
    // Log activity
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, 'KYC Verified', "KYC for client ID $client_id set to $status"]);
    
    echo json_encode(['success' => true]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>