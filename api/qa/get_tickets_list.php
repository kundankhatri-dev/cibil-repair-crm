<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$query = "SELECT id, ticket_no, subject, assigned_to FROM support_tickets WHERE status IN ('resolved', 'closed') ORDER BY id DESC LIMIT 200";
$result = mysqli_query($conn, $query);
$tickets = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tickets[] = $row;
}

echo json_encode(['success' => true, 'tickets' => $tickets]);
?>