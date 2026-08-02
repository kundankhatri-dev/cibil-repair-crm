<?php
// api/lead/get_kanban.php - Get leads grouped by stage for Kanban
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

$stages = ['new', 'contacted', 'analysis', 'proposal', 'converted'];
$result = [];

foreach ($stages as $stage) {
    $query = "SELECT id, name, phone, email, source FROM leads WHERE stage = '$stage' ORDER BY created_at DESC";
    $stage_result = mysqli_query($conn, $query);
    $leads = [];
    while ($row = mysqli_fetch_assoc($stage_result)) {
        $leads[] = $row;
    }
    $result[$stage] = $leads;
}

echo json_encode($result);

mysqli_close($conn);
?>