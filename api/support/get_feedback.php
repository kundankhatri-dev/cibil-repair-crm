<?php
// api/support/get_feedback.php
session_start();
header('Content-Type: application/json');

$allowed_roles = ['support_agent', 'support_team', 'admin', 'manager'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
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

// Create customer_feedback table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS customer_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    ticket_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    feedback TEXT,
    resolved_satisfied BOOLEAN,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (client_id),
    INDEX (ticket_id)
)");

// Get feedback with client and ticket information
$query = "SELECT f.*, u.name as client_name, t.ticket_no 
          FROM customer_feedback f
          JOIN users u ON f.client_id = u.id
          LEFT JOIN support_tickets t ON f.ticket_id = t.id
          ORDER BY f.created_at DESC
          LIMIT 100";

$result = mysqli_query($conn, $query);
$feedback_list = [];

while ($row = mysqli_fetch_assoc($result)) {
    $feedback_list[] = $row;
}

echo json_encode([
    'success' => true,
    'feedback' => $feedback_list
]);

mysqli_close($conn);
?>