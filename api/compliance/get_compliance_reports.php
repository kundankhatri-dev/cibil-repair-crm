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
    
    // Compliance rate by month (last 6 months)
    $labels = [];
    $values = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $labels[] = date('M', strtotime($month));
        
        // Count completed verifications for each month
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM kyc_records 
            WHERE status = 'verified' 
            AND DATE_FORMAT(verified_at, '%Y-%m') = ?
        ");
        $stmt->execute([$month]);
        $verified = (int)$stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM kyc_records 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
        ");
        $stmt->execute([$month]);
        $total = (int)$stmt->fetch()['total'];
        
        $values[] = $total > 0 ? round(($verified / $total) * 100) : 0;
    }
    
    echo json_encode([
        'success' => true,
        'compliance_data' => ['labels' => $labels, 'values' => $values]
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>