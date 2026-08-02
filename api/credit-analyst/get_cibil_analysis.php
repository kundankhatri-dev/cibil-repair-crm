<?php
// api/credit-analyst/get_cibil_analysis.php - CIBIL score analysis
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

// Score distribution
$ranges = [
    'Excellent (750+)' => 0,
    'Good (650-749)' => 0,
    'Average (550-649)' => 0,
    'Poor (300-549)' => 0
];

$dist_query = "SELECT cibil_score FROM credit_analysis WHERE cibil_score IS NOT NULL";
$dist_result = mysqli_query($conn, $dist_query);
while ($row = mysqli_fetch_assoc($dist_result)) {
    $score = $row['cibil_score'];
    if ($score >= 750) $ranges['Excellent (750+)']++;
    elseif ($score >= 650) $ranges['Good (650-749)']++;
    elseif ($score >= 550) $ranges['Average (550-649)']++;
    else $ranges['Poor (300-549)']++;
}

// Client scores
$scores_query = "SELECT ca.*, u.name as client_name 
                 FROM credit_analysis ca
                 JOIN users u ON ca.client_id = u.id
                 WHERE ca.cibil_score IS NOT NULL
                 ORDER BY ca.analyzed_at DESC LIMIT 20";
$scores_result = mysqli_query($conn, $scores_query);
$scores = [];
while ($row = mysqli_fetch_assoc($scores_result)) {
    $scores[] = [
        'client_name' => $row['client_name'],
        'cibil' => $row['cibil_score'],
        'experian' => $row['experian_score'],
        'equifax' => $row['equifax_score'],
        'crif' => $row['crif_score'],
        'updated_at' => $row['analyzed_at']
    ];
}

echo json_encode([
    'success' => true,
    'score_distribution' => [
        'labels' => array_keys($ranges),
        'values' => array_values($ranges)
    ],
    'scores' => $scores
]);

mysqli_close($conn);
?>