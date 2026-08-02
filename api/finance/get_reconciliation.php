<?php
session_start();
header('Content-Type: application/json');
$allowed_roles = ['finance_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$host = 'localhost'; $dbname = 'u929623538_cibil'; $dbuser = 'u929623538_cibilrepair'; $dbpass = 'Kundanlaxmi@1995';
$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS reconciliation (
    id INT PRIMARY KEY AUTO_INCREMENT, transaction_date DATE, description VARCHAR(255),
    bank_amount DECIMAL(12,2), system_amount DECIMAL(12,2), status ENUM('pending','reconciled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$query = "SELECT * FROM reconciliation ORDER BY transaction_date DESC LIMIT 20";
$result = mysqli_query($conn, $query);
$transactions = [];
while ($row = mysqli_fetch_assoc($result)) $transactions[] = $row;

echo json_encode(['success' => true, 'transactions' => $transactions]);
mysqli_close($conn);
?>