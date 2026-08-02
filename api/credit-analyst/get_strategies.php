<?php
// api/credit-analyst/get_strategies.php - Get repair strategies
session_start();
header('Content-Type: application/json');

$allowed_roles = ['credit_analyst', 'admin', 'manager'];
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

// Create strategies table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS repair_strategies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    issue_type VARCHAR(100),
    strategy TEXT,
    estimated_days INT,
    success_rate INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_table);

$query = "SELECT * FROM repair_strategies ORDER BY issue_type";
$result = mysqli_query($conn, $query);
$strategies = [];
while ($row = mysqli_fetch_assoc($result)) {
    $strategies[] = $row;
}

echo json_encode(['success' => true, 'strategies' => $strategies]);

mysqli_close($conn);
?>