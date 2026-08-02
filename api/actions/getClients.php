<?php
// ============================================================
// API Action: Get Clients
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Database connection
$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$query = "SELECT id, name, email, phone, role, status, created_at FROM users WHERE role = 'client' ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$clients = [];
while ($row = mysqli_fetch_assoc($result)) {
    $clients[] = $row;
}

echo json_encode(['success' => true, 'data' => $clients]);
mysqli_close($conn);
?>