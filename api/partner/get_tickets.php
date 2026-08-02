<?php
// api/partner/get_tickets.php
// Partner Get Tickets API - Get all support tickets

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database config
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ========== AUTHENTICATION CHECK ==========
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'redirect' => 'login.html']);
    exit;
}

$partner_id = $_SESSION['user_id'];

// Verify user is actually a partner
$role_check = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
if (!$role_check) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($role_check, "i", $partner_id);
mysqli_stmt_execute($role_check);
$result_role = mysqli_stmt_get_result($role_check);
$role_data = mysqli_fetch_assoc($result_role);

if (!$role_data || $role_data['role'] !== 'partner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// ========== ENSURE TICKETS TABLE EXISTS ==========
$ticketsTable = 'partner_tickets';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$ticketsTable'");
if (mysqli_num_rows($checkTable) == 0) {
    // Create tickets table
    $createTable = "CREATE TABLE IF NOT EXISTS $ticketsTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_id INT NOT NULL,
        ticket_no VARCHAR(20) NOT NULL UNIQUE,
        subject VARCHAR(255) NOT NULL,
        message TEXT,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        status ENUM('open', 'pending', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
        attachment VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_partner_id (partner_id),
        INDEX idx_status (status),
        INDEX idx_ticket_no (ticket_no)
    )";
    mysqli_query($conn, $createTable);
}

// Create replies table if not exists
$repliesTable = 'partner_ticket_replies';
$checkRepliesTable = mysqli_query($conn, "SHOW TABLES LIKE '$repliesTable'");
if (mysqli_num_rows($checkRepliesTable) == 0) {
    $createRepliesTable = "CREATE TABLE IF NOT EXISTS $repliesTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        user_id INT NOT NULL,
        user_type ENUM('partner', 'admin') DEFAULT 'partner',
        message TEXT NOT NULL,
        attachment VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ticket_id (ticket_id),
        FOREIGN KEY (ticket_id) REFERENCES $ticketsTable(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $createRepliesTable);
}

// ========== GET FILTER PARAMETERS ==========
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$priority_filter = isset($_GET['priority']) ? trim($_GET['priority']) : 'all';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Validate limit
if ($limit < 1 || $limit > 200) {
    $limit = 50;
}

// ========== BUILD QUERY ==========
$query = "SELECT t.*, 
          (SELECT COUNT(*) FROM $repliesTable WHERE ticket_id = t.id) as reply_count,
          (SELECT MAX(created_at) FROM $repliesTable WHERE ticket_id = t.id) as last_reply_at
          FROM $ticketsTable t WHERE t.partner_id = ?";
$params = [$partner_id];
$types = "i";

// Add status filter
if ($status_filter != 'all' && !empty($status_filter)) {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add priority filter
if ($priority_filter != 'all' && !empty($priority_filter)) {
    $query .= " AND t.priority = ?";
    $params[] = $priority_filter;
    $types .= "s";
}

$query .= " ORDER BY 
            CASE t.status 
                WHEN 'open' THEN 1 
                WHEN 'pending' THEN 2 
                WHEN 'in_progress' THEN 3 
                WHEN 'resolved' THEN 4 
                WHEN 'closed' THEN 5 
            END ASC,
            t.created_at DESC 
            LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// ========== EXECUTE QUERY ==========
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode([
        'success' => false, 
        'error' => 'Database prepare failed: ' . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tickets = mysqli_fetch_all($result, MYSQLI_ASSOC);

// ========== GET TOTAL COUNT FOR PAGINATION ==========
$countQuery = "SELECT COUNT(*) as total FROM $ticketsTable WHERE partner_id = ?";
$countParams = [$partner_id];
$countTypes = "i";

if ($status_filter != 'all' && !empty($status_filter)) {
    $countQuery .= " AND status = ?";
    $countParams[] = $status_filter;
    $countTypes .= "s";
}

if ($priority_filter != 'all' && !empty($priority_filter)) {
    $countQuery .= " AND priority = ?";
    $countParams[] = $priority_filter;
    $countTypes .= "s";
}

$countStmt = mysqli_prepare($conn, $countQuery);
if ($countStmt) {
    mysqli_stmt_bind_param($countStmt, $countTypes, ...$countParams);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $totalCount = mysqli_fetch_assoc($countResult)['total'] ?? 0;
    mysqli_stmt_close($countStmt);
} else {
    $totalCount = count($tickets);
}

// ========== GET STATS SUMMARY ==========
$statsQuery = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent,
                    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high
                FROM $ticketsTable WHERE partner_id = ?";
$statsStmt = mysqli_prepare($conn, $statsQuery);
$statsData = [
    'total' => 0, 'open' => 0, 'pending' => 0, 'in_progress' => 0,
    'resolved' => 0, 'closed' => 0, 'urgent' => 0, 'high' => 0
];

if ($statsStmt) {
    mysqli_stmt_bind_param($statsStmt, "i", $partner_id);
    mysqli_stmt_execute($statsStmt);
    $statsResult = mysqli_stmt_get_result($statsStmt);
    $statsRow = mysqli_fetch_assoc($statsResult);
    if ($statsRow) {
        $statsData = $statsRow;
    }
    mysqli_stmt_close($statsStmt);
}

// ========== FORMAT RESPONSE DATA ==========
foreach ($tickets as &$ticket) {
    // Generate ticket number if not exists
    if (!isset($ticket['ticket_no']) || empty($ticket['ticket_no'])) {
        $ticket['ticket_no'] = 'TKT' . str_pad($ticket['id'], 5, '0', STR_PAD_LEFT);
    }
    
    // Format dates
    if (isset($ticket['created_at'])) {
        $ticket['created_at_formatted'] = date('d M Y', strtotime($ticket['created_at']));
        $ticket['created_at_full'] = date('d F Y, h:i A', strtotime($ticket['created_at']));
        $ticket['created_at_relative'] = getRelativeTime($ticket['created_at']);
    }
    
    if (isset($ticket['updated_at']) && $ticket['updated_at']) {
        $ticket['updated_at_formatted'] = date('d M Y', strtotime($ticket['updated_at']));
    }
    
    if (isset($ticket['last_reply_at']) && $ticket['last_reply_at']) {
        $ticket['last_reply_formatted'] = date('d M Y', strtotime($ticket['last_reply_at']));
    }
    
    // Priority badge mapping
    $priority_badges = [
        'low' => 'info',
        'medium' => 'warning',
        'high' => 'danger',
        'urgent' => 'danger'
    ];
    $ticket['priority_badge'] = $priority_badges[$ticket['priority']] ?? 'secondary';
    $ticket['priority_label'] = ucfirst($ticket['priority']);
    
    // Status badge mapping
    $status_badges = [
        'open' => 'danger',
        'pending' => 'warning',
        'in_progress' => 'info',
        'resolved' => 'success',
        'closed' => 'secondary'
    ];
    $ticket['status_badge'] = $status_badges[$ticket['status']] ?? 'secondary';
    $ticket['status_label'] = ucfirst(str_replace('_', ' ', $ticket['status']));
    
    // Truncate message for preview
    $ticket['message_preview'] = isset($ticket['message']) ? 
        (strlen($ticket['message']) > 100 ? substr($ticket['message'], 0, 100) . '...' : $ticket['message']) : '';
    
    // Reply count display
    $ticket['reply_count'] = (int)($ticket['reply_count'] ?? 0);
    $ticket['has_replies'] = $ticket['reply_count'] > 0;
}

// Helper function for relative time
function getRelativeTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('d M Y', $time);
}

// ========== RETURN JSON RESPONSE ==========
echo json_encode([
    'success' => true,
    'data' => $tickets,
    'total' => count($tickets),
    'total_all' => (int)$totalCount,
    'has_more' => ($offset + $limit) < $totalCount,
    'stats' => [
        'total' => (int)($statsData['total'] ?? 0),
        'open' => (int)($statsData['open'] ?? 0),
        'pending' => (int)($statsData['pending'] ?? 0),
        'in_progress' => (int)($statsData['in_progress'] ?? 0),
        'resolved' => (int)($statsData['resolved'] ?? 0),
        'closed' => (int)($statsData['closed'] ?? 0),
        'urgent' => (int)($statsData['urgent'] ?? 0),
        'high' => (int)($statsData['high'] ?? 0)
    ],
    'filters' => [
        'status' => $status_filter,
        'priority' => $priority_filter,
        'limit' => $limit,
        'offset' => $offset
    ],
    'pagination' => [
        'current_page' => floor($offset / $limit) + 1,
        'per_page' => $limit,
        'total_pages' => ceil($totalCount / $limit),
        'total_records' => (int)$totalCount
    ]
]);

// ========== CLEAN UP ==========
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($role_check)) mysqli_stmt_close($role_check);

mysqli_close($conn);
?>