<?php
// api/credit-analyst/get_issues_stats.php - Issues statistics
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

$stats = [
    'written_off' => 0,
    'settled' => 0,
    'late_payment' => 0,
    'incorrect_enquiries' => 0,
    'duplicate_loans' => 0,
    'identity_mismatch' => 0,
    'overdue' => 0
];

$query = "SELECT issue_type, COUNT(*) as count FROM credit_issues GROUP BY issue_type";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $type = strtolower(str_replace(' ', '_', $row['issue_type']));
    if (isset($stats[$type])) {
        $stats[$type] = (int)$row['count'];
    }
}

echo json_encode([
    'success' => true,
    'written_off' => $stats['written_off'],
    'settled' => $stats['settled'],
    'overdue' => $stats['overdue'],
    'incorrect_enquiries' => $stats['incorrect_enquiries'],
    'duplicate_loans' => $stats['duplicate_loans']
]);

mysqli_close($conn);
?>