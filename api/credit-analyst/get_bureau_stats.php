<?php
// api/credit-analyst/get_bureau_stats.php - Bureau statistics
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

$bureaus = ['cibil_score', 'experian_score', 'equifax_score', 'crif_score'];
$bureau_names = ['CIBIL', 'Experian', 'Equifax', 'CRIF'];
$averages = [];

foreach ($bureaus as $i => $bureau) {
    $query = "SELECT AVG($bureau) as avg FROM credit_analysis WHERE $bureau IS NOT NULL";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $averages[] = round($row['avg'] ?? 0);
}

echo json_encode([
    'success' => true,
    'cibil_avg' => $averages[0],
    'experian_avg' => $averages[1],
    'equifax_avg' => $averages[2],
    'crif_avg' => $averages[3],
    'bureau_data' => [
        'labels' => $bureau_names,
        'values' => $averages
    ]
]);

mysqli_close($conn);
?>