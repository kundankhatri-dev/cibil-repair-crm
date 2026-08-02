<?php
// api/followup/list.php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Build query
$query = "SELECT f.*, l.customer_name, l.customer_phone, l.service_type as service,
          CASE 
              WHEN f.followup_date < NOW() AND f.status = 'pending' THEN 'overdue'
              WHEN f.followup_date <= DATE_ADD(NOW(), INTERVAL 24 HOUR) AND f.status = 'pending' THEN 'upcoming'
              ELSE f.status
          END as display_status
          FROM followups f
          JOIN partner_leads l ON f.lead_id = l.id
          WHERE f.partner_id = ?";

$params = [$partner_id];
$types = "i";

if ($status !== 'all') {
    if ($status === 'overdue') {
        $query .= " AND f.followup_date < NOW() AND f.status = 'pending'";
    } elseif ($status === 'upcoming') {
        $query .= " AND f.followup_date <= DATE_ADD(NOW(), INTERVAL 24 HOUR) AND f.followup_date >= NOW() AND f.status = 'pending'";
    } else {
        $query .= " AND f.status = ?";
        $params[] = $status;
        $types .= "s";
    }
}

$query .= " ORDER BY 
            CASE f.priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END ASC,
            f.followup_date ASC 
            LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$followups = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get count
$count_query = "SELECT COUNT(*) as total FROM followups WHERE partner_id = ?";
$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $partner_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total = mysqli_fetch_assoc($count_result)['total'];

echo json_encode([
    'success' => true,
    'followups' => $followups,
    'total' => count($followups),
    'total_all' => (int)$total,
    'has_more' => ($offset + $limit) < $total
]);

mysqli_close($conn);
?>