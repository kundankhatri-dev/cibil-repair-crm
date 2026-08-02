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
    $agreement_type = $input['agreement_type'] ?? '';
    $terms = $input['terms'] ?? '';
    $issue_date = $input['issue_date'] ?? date('Y-m-d');
    $expiry_date = $input['expiry_date'] ?? null;
    
    if (!$client_id || !$agreement_type) {
        echo json_encode(['success' => false, 'error' => 'Client and agreement type are required']);
        exit;
    }
    
    $agreement_no = 'AG-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $stmt = $pdo->prepare("
        INSERT INTO client_agreements (agreement_no, client_id, agreement_type, terms, issue_date, expiry_date, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'draft', NOW())
    ");
    $stmt->execute([$agreement_no, $client_id, $agreement_type, $terms, $issue_date, $expiry_date]);
    
    // Log activity
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], 'Agreement Created', "Agreement #$agreement_no created for client ID $client_id"]);
    
    echo json_encode(['success' => true, 'agreement_no' => $agreement_no]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>