<?php
// api/credit-analyst/get_analyzed_reports.php - Get analyzed reports
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

$query = "SELECT ca.*, u.name as client_name, a.name as analyst_name,
          LENGTH(ca.issues) - LENGTH(REPLACE(ca.issues, ',', '')) + 1 as issues_found
          FROM credit_analysis ca
          JOIN users u ON ca.client_id = u.id
          LEFT JOIN users a ON ca.analyst_id = a.id
          WHERE ca.status = 'analyzed'
          ORDER BY ca.analyzed_at DESC";
$result = mysqli_query($conn, $query);
$reports = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reports[] = [
        'id' => $row['id'],
        'client_name' => $row['client_name'],
        'cibil_score' => $row['cibil_score'],
        'issues_found' => $row['issues_found'] ?: 0,
        'analyst_name' => $row['analyst_name'],
        'analyzed_at' => $row['analyzed_at']
    ];
}

echo json_encode(['success' => true, 'reports' => $reports]);

mysqli_close($conn);
?>