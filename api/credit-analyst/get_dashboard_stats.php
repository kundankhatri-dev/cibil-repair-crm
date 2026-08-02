<?php
// api/credit-analyst/get_dashboard_stats.php - Dashboard Statistics
session_start();
header('Content-Type: application/json');

$allowed_roles = ['credit_analyst', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Create credit_analysis table if not exists
$create_analysis = "CREATE TABLE IF NOT EXISTS credit_analysis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    report_id INT,
    cibil_score INT,
    experian_score INT,
    equifax_score INT,
    crif_score INT,
    issues TEXT,
    analyst_notes TEXT,
    analyst_id INT,
    status ENUM('pending', 'analyzed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    analyzed_at DATETIME,
    INDEX idx_client (client_id),
    INDEX idx_status (status)
)";
mysqli_query($conn, $create_analysis);

// Create credit_issues table
$create_issues = "CREATE TABLE IF NOT EXISTS credit_issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    analysis_id INT,
    issue_type VARCHAR(100),
    bank_name VARCHAR(100),
    amount DECIMAL(12,2),
    description TEXT,
    status ENUM('pending', 'disputed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client (client_id)
)";
mysqli_query($conn, $create_issues);

// Total reports analyzed
$total_query = "SELECT COUNT(*) as total FROM credit_analysis WHERE status = 'analyzed'";
$total_result = mysqli_query($conn, $total_query);
$total_reports = mysqli_fetch_assoc($total_result)['total'] ?? 0;

// Pending reports
$pending_query = "SELECT COUNT(*) as pending FROM credit_analysis WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_reports = mysqli_fetch_assoc($pending_result)['pending'] ?? 0;

// Average CIBIL score
$avg_query = "SELECT AVG(cibil_score) as avg_score FROM credit_analysis WHERE cibil_score IS NOT NULL AND status = 'analyzed'";
$avg_result = mysqli_query($conn, $avg_query);
$avg_data = mysqli_fetch_assoc($avg_result);
$avg_cibil_score = round($avg_data['avg_score'] ?? 0);

// Total issues
$issues_query = "SELECT COUNT(*) as total FROM credit_issues";
$issues_result = mysqli_query($conn, $issues_query);
$total_issues = mysqli_fetch_assoc($issues_result)['total'] ?? 0;

// Issue distribution
$issue_dist = ['written_off' => 0, 'settled' => 0, 'late_payment' => 0, 'incorrect_enquiry' => 0, 'duplicate_loan' => 0, 'identity_mismatch' => 0, 'overdue' => 0];
$issue_query = "SELECT issue_type, COUNT(*) as count FROM credit_issues GROUP BY issue_type";
$issue_result = mysqli_query($conn, $issue_query);
while ($row = mysqli_fetch_assoc($issue_result)) {
    $type = str_replace(' ', '_', strtolower($row['issue_type']));
    $issue_dist[$type] = (int)$row['count'];
}

// Recent analyses
$recent_query = "SELECT ca.*, u.name as client_name, a.name as analyst_name 
                 FROM credit_analysis ca
                 JOIN users u ON ca.client_id = u.id
                 LEFT JOIN users a ON ca.analyst_id = a.id
                 ORDER BY ca.created_at DESC LIMIT 10";
$recent_result = mysqli_query($conn, $recent_query);
$recent_analyses = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    $issues_count = !empty($row['issues']) ? count(explode(',', $row['issues'])) : 0;
    $recent_analyses[] = [
        'id' => $row['id'],
        'client_name' => $row['client_name'],
        'cibil_score' => $row['cibil_score'],
        'experian_score' => $row['experian_score'],
        'equifax_score' => $row['equifax_score'],
        'issues_found' => $issues_count,
        'analyst_name' => $row['analyst_name'],
        'created_at' => $row['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'total_reports' => $total_reports,
    'pending_reports' => $pending_reports,
    'avg_cibil_score' => $avg_cibil_score,
    'total_issues' => $total_issues,
    'issue_distribution' => [
        'labels' => ['Written Off', 'Settled', 'Late Payment', 'Incorrect Enquiry', 'Duplicate Loan', 'Identity Mismatch', 'Overdue'],
        'values' => array_values($issue_dist)
    ],
    'recent_analyses' => $recent_analyses
]);

mysqli_close($conn);
?>