<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['dispute_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS rbi_complaints (
    id INT PRIMARY KEY AUTO_INCREMENT, client_id INT, complaint_id VARCHAR(50),
    bank VARCHAR(100), filed_date DATE, status VARCHAR(30), notes TEXT
)");
$query = "SELECT r.*, u.name as client_name FROM rbi_complaints r JOIN users u ON r.client_id = u.id ORDER BY r.filed_date DESC";
$result = mysqli_query($conn, $query);
$complaints = [];
while($row = mysqli_fetch_assoc($result)) $complaints[] = $row;
echo json_encode(['success'=>true, 'complaints'=>$complaints]);
mysqli_close($conn);
?>