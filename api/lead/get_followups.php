<?php
// api/lead/get_followups.php - Get pending follow-ups
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

$query = "SELECT f.*, l.name as lead_name, l.phone as lead_phone 
          FROM lead_followups f
          JOIN leads l ON f.lead_id = l.id
          WHERE f.status = 'pending' AND f.followup_date <= NOW()
          ORDER BY f.followup_date ASC";
$result = mysqli_query($conn, $query);
$followups = [];
while ($row = mysqli_fetch_assoc($result)) {
    $followups[] = $row;
}

echo json_encode(['success' => true, 'followups' => $followups]);

mysqli_close($conn);
?>