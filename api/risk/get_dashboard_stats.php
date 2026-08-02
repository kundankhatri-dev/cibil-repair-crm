<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$response = [];

try {
    // Pending alerts count
    $query = "SELECT COUNT(*) as pending FROM risk_fraud_alerts WHERE resolution_status = 'pending'";
    $result = mysqli_query($conn, $query);
    $pending_alerts = mysqli_fetch_assoc($result)['pending'];
    
    // Critical alerts
    $query = "SELECT COUNT(*) as critical FROM risk_fraud_alerts WHERE severity = 'critical' AND resolution_status = 'pending'";
    $result = mysqli_query($conn, $query);
    $critical_alerts = mysqli_fetch_assoc($result)['critical'];
    
    // Average risk score
    $query = "SELECT AVG(risk_score) as avg_score FROM risk_profiles";
    $result = mysqli_query($conn, $query);
    $avg_risk_score = mysqli_fetch_assoc($result)['avg_score'] ?? 0;
    
    // Critical risk clients
    $query = "SELECT COUNT(*) as critical_count FROM risk_profiles WHERE risk_level = 'critical'";
    $result = mysqli_query($conn, $query);
    $critical_risk_clients = mysqli_fetch_assoc($result)['critical_count'];
    
    // Open breaches
    $query = "SELECT COUNT(*) as open_breaches FROM risk_compliance_breaches WHERE status IN ('open', 'investigating')";
    $result = mysqli_query($conn, $query);
    $open_breaches = mysqli_fetch_assoc($result)['open_breaches'];
    
    // Open audit findings
    $query = "SELECT COUNT(*) as open_findings FROM risk_audit_findings WHERE status NOT IN ('closed', 'remediated')";
    $result = mysqli_query($conn, $query);
    $open_findings = mysqli_fetch_assoc($result)['open_findings'];
    
    // Risk distribution for chart
    $query = "SELECT risk_level, COUNT(*) as count FROM risk_profiles GROUP BY risk_level";
    $result = mysqli_query($conn, $query);
    $risk_distribution = ['labels' => [], 'values' => []];
    while ($row = mysqli_fetch_assoc($result)) {
        $risk_distribution['labels'][] = ucfirst($row['risk_level']);
        $risk_distribution['values'][] = (int)$row['count'];
    }
    
    // Recent alerts
    $query = "SELECT * FROM risk_fraud_alerts ORDER BY triggered_at DESC LIMIT 5";
    $result = mysqli_query($conn, $query);
    $recent_alerts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_alerts[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'pending_alerts' => (int)$pending_alerts,
        'critical_alerts' => (int)$critical_alerts,
        'avg_risk_score' => round($avg_risk_score, 2),
        'critical_risk_clients' => (int)$critical_risk_clients,
        'open_breaches' => (int)$open_breaches,
        'open_audit_findings' => (int)$open_findings,
        'risk_distribution' => $risk_distribution,
        'recent_alerts' => $recent_alerts
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>