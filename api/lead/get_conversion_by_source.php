<?php
// api/lead/get_conversion_by_source.php - Get conversion rates by source
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
$results = [];

foreach ($sources as $source) {
    $total_query = "SELECT COUNT(*) as total FROM leads WHERE source = '$source'";
    $total_result = mysqli_query($conn, $total_query);
    $total = mysqli_fetch_assoc($total_result)['total'] ?? 0;
    
    $converted_query = "SELECT COUNT(*) as converted FROM leads WHERE source = '$source' AND stage = 'converted'";
    $converted_result = mysqli_query($conn, $converted_query);
    $converted = mysqli_fetch_assoc($converted_result)['converted'] ?? 0;
    
    $percentage = $total > 0 ? round(($converted / $total) * 100, 1) : 0;
    
    $results[] = [
        'source' => ucfirst($source),
        'total' => $total,
        'converted' => $converted,
        'percentage' => $percentage
    ];
}

echo json_encode(['success' => true, 'sources' => $results]);

mysqli_close($conn);
?>