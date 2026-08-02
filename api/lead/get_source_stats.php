<?php
// api/lead/get_source_stats.php - Get lead source statistics
session_start();
header('Content-Type: application/json');

$allowed_roles = ['sales', 'bd', 'admin', 'manager'];
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

$sources = ['website', 'whatsapp', 'facebook', 'google', 'referral'];
$stats = [];
$source_performance_labels = [];
$source_performance_values = [];

foreach ($sources as $source) {
    $query = "SELECT COUNT(*) as total FROM leads WHERE source = '$source'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $stats[$source] = (int)$row['total'];
    $source_performance_labels[] = ucfirst($source);
    $source_performance_values[] = (int)$row['total'];
}

echo json_encode([
    'success' => true,
    'website' => $stats['website'] ?? 0,
    'whatsapp' => $stats['whatsapp'] ?? 0,
    'facebook' => $stats['facebook'] ?? 0,
    'google' => $stats['google'] ?? 0,
    'referral' => $stats['referral'] ?? 0,
    'source_performance' => [
        'labels' => $source_performance_labels,
        'values' => $source_performance_values
    ]
]);

mysqli_close($conn);
?>