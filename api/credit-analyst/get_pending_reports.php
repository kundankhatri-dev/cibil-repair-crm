<?php
// api/credit-analyst/get_pending_reports.php - Get pending credit reports
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

// Get pending credit analyses
$query = "SELECT ca.*, u.name as client_name 
          FROM credit_analysis ca
          JOIN users u ON ca.client_id = u.id
          WHERE ca.status = 'pending'
          ORDER BY ca.created_at ASC";
$result = mysqli_query($conn, $query);
$reports = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reports[] = [
        'id' => $row['id'],
        'client_name' => $row['client_name'],
        'client_id' => $row['client_id'],
        'uploaded_at' => $row['created_at'],
        'bureau' => 'CIBIL'
    ];
}

echo json_encode(['success' => true, 'reports' => $reports]);

mysqli_close($conn);
?>