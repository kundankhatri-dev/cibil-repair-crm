<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['dispute_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) { echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit; }

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS dispute_documents (
    id INT PRIMARY KEY AUTO_INCREMENT, dispute_id INT, document_name VARCHAR(200),
    doc_type VARCHAR(50), file_path VARCHAR(500), uploaded_by INT, uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$query = "SELECT d.*, u.name as uploaded_by FROM dispute_documents d LEFT JOIN users u ON d.uploaded_by = u.id ORDER BY d.uploaded_at DESC";
$result = mysqli_query($conn, $query);
$documents = [];
while($row = mysqli_fetch_assoc($result)) $documents[] = $row;
echo json_encode(['success'=>true, 'documents'=>$documents]);
mysqli_close($conn);
?>