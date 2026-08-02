<?php
header('Content-Type: application/json');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Connection failed']);
    exit;
}

$users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$leads = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM partner_leads"))['count'];

echo json_encode([
    'success' => true,
    'total_users' => $users,
    'total_leads' => $leads
]);

mysqli_close($conn);
?>