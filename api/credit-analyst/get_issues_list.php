<?php
// api/credit-analyst/get_issues_list.php - Get all issues list
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

$query = "SELECT ci.*, u.name as client_name 
          FROM credit_issues ci
          JOIN users u ON ci.client_id = u.id
          ORDER BY ci.created_at DESC LIMIT 50";
$result = mysqli_query($conn, $query);
$issues = [];
while ($row = mysqli_fetch_assoc($result)) {
    $type_label = str_replace('_', ' ', $row['issue_type']);
    $type_code = str_replace(' ', '_', strtolower($row['issue_type']));
    $issues[] = [
        'id' => $row['id'],
        'client_name' => $row['client_name'],
        'type' => $type_code,
        'type_label' => $type_label,
        'bank_name' => $row['bank_name'],
        'amount' => $row['amount'],
        'status' => $row['status']
    ];
}

echo json_encode(['success' => true, 'issues' => $issues]);

mysqli_close($conn);
?>