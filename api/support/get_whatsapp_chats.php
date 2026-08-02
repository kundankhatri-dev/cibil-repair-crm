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

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM whatsapp_chats"))['c'] ?? 0;
$unread = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM whatsapp_chats WHERE is_read=0"))['c'] ?? 0;
$resolved = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM whatsapp_chats WHERE status='resolved'"))['c'] ?? 0;

$query = "SELECT w.*, u.name as customer_name FROM whatsapp_chats w JOIN users u ON w.client_id = u.id ORDER BY w.created_at DESC LIMIT 20";
$result = mysqli_query($conn, $query);
$chats = [];
while ($row = mysqli_fetch_assoc($result)) $chats[] = $row;

echo json_encode(['success' => true, 'total' => $total, 'unread' => $unread, 'resolved' => $resolved, 'avg_response' => '2h', 'chats' => $chats]);
mysqli_close($conn);
?>