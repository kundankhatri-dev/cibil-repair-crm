<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/database.php';

$agent_id = $_GET['agent_id'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
if ($agent_id) $where[] = "tr.agent_id = $agent_id";
if ($search) $where[] = "(t.subject LIKE '%$search%' OR t.ticket_no LIKE '%$search%')";

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT tr.*, u.name as agent_name, t.subject as ticket_subject 
          FROM qa_ticket_reviews tr
          JOIN users u ON tr.agent_id = u.id
          LEFT JOIN support_tickets t ON tr.ticket_id = t.id
          $where_clause
          ORDER BY tr.review_date DESC";
$result = mysqli_query($conn, $query);
$reviews = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reviews[] = $row;
}

echo json_encode(['success' => true, 'reviews' => $reviews, 'total' => count($reviews)]);
?>