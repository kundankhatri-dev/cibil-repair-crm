<?php
session_start();
header('Content-Type: application/json');

$allowed_roles = ['support_team', 'admin', 'manager', 'partner'];
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

mysqli_set_charset($conn, 'utf8mb4');

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_email = $_SESSION['user_email'] ?? '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$priority = isset($_GET['priority']) ? mysqli_real_escape_string($conn, $_GET['priority']) : '';

if ($user_role === 'partner') {
    // Partner sees only their tickets (by client_email)
    $query = "SELECT t.* FROM support_tickets t WHERE t.client_email = '$user_email'";
} else {
    // Admin/Support sees all tickets
    $query = "SELECT t.* FROM support_tickets t";
    
    $where = [];
    if ($status) $where[] = "t.status = '$status'";
    if ($priority) $where[] = "t.priority = '$priority'";
    if ($where) $query .= " WHERE " . implode(' AND ', $where);
}

$query .= " ORDER BY t.created_at DESC";

$result = mysqli_query($conn, $query);
$tickets = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tickets[] = $row;
    }
}

echo json_encode(['success' => true, 'tickets' => $tickets]);
mysqli_close($conn);
?>