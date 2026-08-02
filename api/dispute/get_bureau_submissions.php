<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['dispute_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS bureau_disputes (
    id INT PRIMARY KEY AUTO_INCREMENT, dispute_id INT, client_id INT, bureau VARCHAR(50),
    dispute_no VARCHAR(50), submission_date DATE, expected_response DATE, status VARCHAR(30)
)");
$query = "SELECT b.*, u.name as client_name FROM bureau_disputes b JOIN users u ON b.client_id = u.id ORDER BY b.submission_date DESC";
$result = mysqli_query($conn, $query);
$submissions = [];
while($row = mysqli_fetch_assoc($result)) $submissions[] = $row;
echo json_encode(['success'=>true, 'submissions'=>$submissions]);
mysqli_close($conn);
?>