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
    
    // Total agreements
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM client_agreements");
    $total_agreements = (int)$stmt->fetch()['total'];
    
    // KYC completed
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM kyc_records WHERE status = 'verified'");
    $kyc_completed = (int)$stmt->fetch()['total'];
    
    // Consent given
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM consent_forms WHERE status = 'provided'");
    $consent_given = (int)$stmt->fetch()['total'];
    
    // Pending reviews
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM kyc_records WHERE status = 'pending'");
    $pending_reviews = (int)$stmt->fetch()['total'];
    
    // KYC distribution
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM kyc_records GROUP BY status");
    $distribution = ['pending' => 0, 'verified' => 0, 'rejected' => 0];
    while ($row = $stmt->fetch()) {
        $distribution[$row['status']] = (int)$row['count'];
    }
    
    // Recent activities - agreements
    $stmt = $pdo->query("
        SELECT a.*, c.name as client_name 
        FROM client_agreements a 
        LEFT JOIN customers c ON a.client_id = c.id 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
    $recent = $stmt->fetchAll();
    
    $recent_activities = [];
    foreach ($recent as $r) {
        $recent_activities[] = [
            'client_name' => $r['client_name'] ?? 'Unknown',
            'document_type' => $r['agreement_type'] ?? 'Agreement',
            'status' => $r['status'] ?? 'draft',
            'date' => date('d M Y', strtotime($r['created_at'] ?? 'now'))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'total_agreements' => $total_agreements,
        'kyc_completed' => $kyc_completed,
        'consent_given' => $consent_given,
        'pending_reviews' => $pending_reviews,
        'kyc_distribution' => [
            'labels' => ['Pending', 'Verified', 'Rejected'],
            'values' => [$distribution['pending'], $distribution['verified'], $distribution['rejected']]
        ],
        'recent_activities' => $recent_activities
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>