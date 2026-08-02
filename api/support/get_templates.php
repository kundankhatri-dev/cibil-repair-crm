<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['support_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
$result = mysqli_query($conn, "SELECT * FROM reply_templates ORDER BY category, title");
$templates = [];
while ($row = mysqli_fetch_assoc($result)) $templates[] = $row;
echo json_encode(['success' => true, 'templates' => $templates]);
mysqli_close($conn);
?>