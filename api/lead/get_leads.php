<?php
// api/lead/get_leads.php - Get all leads
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

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$stage = isset($_GET['stage']) ? mysqli_real_escape_string($conn, $_GET['stage']) : '';
$source = isset($_GET['source']) ? mysqli_real_escape_string($conn, $_GET['source']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$query = "SELECT l.* FROM leads l WHERE 1=1";
if ($search) {
    $query .= " AND (l.name LIKE '%$search%' OR l.phone LIKE '%$search%' OR l.email LIKE '%$search%')";
}
if ($stage) {
    $query .= " AND l.stage = '$stage'";
}
if ($source) {
    $query .= " AND l.source = '$source'";
}
$query .= " ORDER BY l.created_at DESC LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);
$leads = [];
while ($row = mysqli_fetch_assoc($result)) {
    $score = 50;
    if ($row['source'] == 'referral') $score += 20;
    if ($row['source'] == 'website') $score += 10;
    if ($row['stage'] == 'converted') $score += 30;
    if ($row['stage'] == 'analysis') $score += 15;
    $row['score'] = min(100, $score);
    $leads[] = $row;
}

echo json_encode([
    'success' => true,
    'leads' => $leads,
    'total' => count($leads)
]);

mysqli_close($conn);
?>