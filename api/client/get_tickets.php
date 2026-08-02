<?php
// api/client/get_tickets.php - Get all support tickets for client
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'u929623538_cibil';
$dbuser = 'u929623538_cibilrepair';
$dbpass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($host, $dbuser, $dbpass, $dbname);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get client_id (supports both client and partner/admin viewing)
$client_id = $_SESSION['client_id'] ?? $_SESSION['user_id'] ?? null;
$viewer_role = $_SESSION['user_role'] ?? 'client';
$viewer_id = $_SESSION['user_id'] ?? null;

// If partner or admin viewing, allow client_id from GET
if (in_array($viewer_role, ['admin', 'partner']) && isset($_GET['client_id'])) {
    $client_id = (int)$_GET['client_id'];
    
    // Verify partner has access to this client
    if ($viewer_role === 'partner' && $viewer_id) {
        $check = mysqli_prepare($conn, "SELECT COUNT(*) FROM leads WHERE partner_id = ? AND customer_id = ?");
        mysqli_stmt_bind_param($check, "ii", $viewer_id, $client_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_bind_result($check, $count);
        mysqli_stmt_fetch($check);
        mysqli_stmt_close($check);
        
        if ($count == 0) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            exit;
        }
    }
}

if (!$client_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// ========== CREATE TICKETS TABLE ==========
$create_tickets = "CREATE TABLE IF NOT EXISTS support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_no VARCHAR(50) UNIQUE,
    client_id INT NOT NULL,
    case_id INT,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'waiting', 'resolved', 'closed') DEFAULT 'open',
    category VARCHAR(50) DEFAULT 'general',
    assigned_to INT,
    assigned_at DATETIME,
    resolved_at DATETIME,
    closed_at DATETIME,
    rating TINYINT,
    rating_comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created (created_at)
)";

mysqli_query($conn, $create_tickets);

// ========== CREATE TICKET REPLIES TABLE ==========
$create_replies = "CREATE TABLE IF NOT EXISTS ticket_replies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('client', 'agent', 'admin') DEFAULT 'client',
    message TEXT NOT NULL,
    attachment VARCHAR(500),
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket (ticket_id),
    INDEX idx_read (is_read)
)";

mysqli_query($conn, $create_replies);

// ========== CREATE TICKET CATEGORIES TABLE ==========
$create_categories = "CREATE TABLE IF NOT EXISTS ticket_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT DEFAULT 1,
    sort_order INT DEFAULT 0
)";

mysqli_query($conn, $create_categories);

// Insert default categories if empty
$check_cats = mysqli_query($conn, "SELECT COUNT(*) FROM ticket_categories");
if ($check_cats) {
    $cat_count = mysqli_fetch_row($check_cats)[0];
    if ($cat_count == 0) {
        $default_cats = [
            ['General Inquiry', 'General questions about services', 1],
            ['Technical Issue', 'Problems with dashboard or login', 2],
            ['Payment Related', 'Issues with payments, invoices, refunds', 3],
            ['Case Status', 'Updates about your credit repair case', 4],
            ['Document Support', 'Help with document upload and verification', 5],
            ['Dispute Assistance', 'Help with filing or tracking disputes', 6],
            ['Report Error', 'Report incorrect information in CIBIL report', 7],
            ['Feedback', 'Share your feedback or suggestions', 8]
        ];
        $insert_cat = mysqli_prepare($conn, "INSERT INTO ticket_categories (name, description, sort_order) VALUES (?, ?, ?)");
        foreach ($default_cats as $cat) {
            mysqli_stmt_bind_param($insert_cat, "ssi", $cat[0], $cat[1], $cat[2]);
            mysqli_stmt_execute($insert_cat);
        }
        mysqli_stmt_close($insert_cat);
    }
}

// Generate ticket number function
function generateTicketNo($conn, $client_id) {
    $prefix = 'TKT';
    $year = date('Y');
    $month = date('m');
    
    $query = "SELECT ticket_no FROM support_tickets WHERE ticket_no LIKE '$prefix$year$month%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_num = (int)substr($row['ticket_no'], -6);
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    
    return $prefix . $year . $month . str_pad($new_num, 6, '0', STR_PAD_LEFT);
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$priority_filter = isset($_GET['priority']) ? trim($_GET['priority']) : 'all';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if ($limit < 1 || $limit > 100) {
    $limit = 20;
}

// ========== BUILD QUERY ==========
$query = "SELECT 
            t.*,
            DATE_FORMAT(t.created_at, '%d %b %Y') as created_date_formatted,
            DATE_FORMAT(t.created_at, '%h:%i %p') as created_time,
            DATE_FORMAT(t.updated_at, '%d %b %Y') as updated_date_formatted,
            DATE_FORMAT(t.resolved_at, '%d %b %Y') as resolved_date_formatted,
            (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id AND is_read = 0 AND user_type != 'client') as unread_replies,
            (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.id) as total_replies
          FROM support_tickets t
          WHERE t.client_id = ?";

$params = [$client_id];
$types = "i";

if ($status_filter !== 'all') {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($priority_filter !== 'all') {
    $query .= " AND t.priority = ?";
    $params[] = $priority_filter;
    $types .= "s";
}

if ($category_filter !== 'all') {
    $query .= " AND t.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (t.ticket_no LIKE ? OR t.subject LIKE ? OR t.message LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY 
            CASE t.status 
                WHEN 'open' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'waiting' THEN 3
                WHEN 'resolved' THEN 4
                WHEN 'closed' THEN 5
            END ASC,
            t.updated_at DESC 
            LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$tickets = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== GET TICKET COUNTS ==========
$count_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent,
                    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN priority = 'low' THEN 1 ELSE 0 END) as low
                FROM support_tickets WHERE client_id = ?";

$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $client_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$counts = mysqli_fetch_assoc($count_result);
mysqli_stmt_close($count_stmt);

// ========== GET CATEGORIES ==========
$cat_query = "SELECT id, name, description FROM ticket_categories WHERE is_active = 1 ORDER BY sort_order";
$cat_result = mysqli_query($conn, $cat_query);
$categories = mysqli_fetch_all($cat_result, MYSQLI_ASSOC);

// ========== GET TOTAL COUNT FOR PAGINATION ==========
$total_query = "SELECT COUNT(*) as total FROM support_tickets WHERE client_id = ?";
$total_params = [$client_id];
$total_types = "i";

if ($status_filter !== 'all') {
    $total_query .= " AND status = ?";
    $total_params[] = $status_filter;
    $total_types .= "s";
}
if ($priority_filter !== 'all') {
    $total_query .= " AND priority = ?";
    $total_params[] = $priority_filter;
    $total_types .= "s";
}
if ($category_filter !== 'all') {
    $total_query .= " AND category = ?";
    $total_params[] = $category_filter;
    $total_types .= "s";
}
if (!empty($search)) {
    $total_query .= " AND (ticket_no LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $total_params[] = $search_param;
    $total_params[] = $search_param;
    $total_params[] = $search_param;
    $total_types .= "sss";
}

$total_stmt = mysqli_prepare($conn, $total_query);
mysqli_stmt_bind_param($total_stmt, $total_types, ...$total_params);
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_data = mysqli_fetch_assoc($total_result);
$total_count = $total_data['total'] ?? 0;
mysqli_stmt_close($total_stmt);

// ========== FORMAT TICKETS ==========
$status_labels = [
    'open' => 'Open',
    'in_progress' => 'In Progress',
    'waiting' => 'Waiting for Response',
    'resolved' => 'Resolved',
    'closed' => 'Closed'
];

$status_colors = [
    'open' => 'danger',
    'in_progress' => 'info',
    'waiting' => 'warning',
    'resolved' => 'success',
    'closed' => 'secondary'
];

$priority_labels = [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
    'urgent' => 'Urgent'
];

$priority_colors = [
    'low' => 'secondary',
    'medium' => 'info',
    'high' => 'warning',
    'urgent' => 'danger'
];

$priority_icons = [
    'low' => 'fa-arrow-down',
    'medium' => 'fa-minus',
    'high' => 'fa-arrow-up',
    'urgent' => 'fa-exclamation-triangle'
];

foreach ($tickets as &$t) {
    $t['status_label'] = $status_labels[$t['status']] ?? ucfirst($t['status']);
    $t['status_badge'] = $status_colors[$t['status']] ?? 'secondary';
    $t['priority_label'] = $priority_labels[$t['priority']] ?? ucfirst($t['priority']);
    $t['priority_badge'] = $priority_colors[$t['priority']] ?? 'secondary';
    $t['priority_icon'] = $priority_icons[$t['priority']] ?? 'fa-tag';
    
    // Calculate response time
    if ($t['created_at']) {
        $created = strtotime($t['created_at']);
        $now = time();
        $hours_ago = floor(($now - $created) / 3600);
        
        if ($hours_ago < 1) {
            $t['time_ago'] = 'Just now';
        } elseif ($hours_ago < 24) {
            $t['time_ago'] = $hours_ago . ' hour(s) ago';
        } else {
            $t['time_ago'] = floor($hours_ago / 24) . ' day(s) ago';
        }
    }
    
    // Check if waiting for client response
    if ($t['status'] === 'waiting' && $t['unread_replies'] > 0) {
        $t['needs_response'] = true;
    } else {
        $t['needs_response'] = false;
    }
    
    // Truncate message for preview
    $t['message_preview'] = strlen($t['message']) > 100 
        ? substr($t['message'], 0, 100) . '...' 
        : $t['message'];
    
    // URLs
    $t['view_url'] = "api/client/get_ticket_details.php?id={$t['id']}";
    $t['reply_url'] = "api/client/reply_ticket.php?id={$t['id']}";
}

// ========== GET AVERAGE RESPONSE TIME ==========
$avg_query = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, first_reply_at)) as avg_response_hours
              FROM (
                  SELECT t.created_at, MIN(r.created_at) as first_reply_at
                  FROM support_tickets t
                  JOIN ticket_replies r ON t.id = r.ticket_id
                  WHERE t.client_id = ? AND r.user_type != 'client'
                  GROUP BY t.id
              ) as replies";

$avg_stmt = mysqli_prepare($conn, $avg_query);
mysqli_stmt_bind_param($avg_stmt, "i", $client_id);
mysqli_stmt_execute($avg_stmt);
$avg_result = mysqli_stmt_get_result($avg_stmt);
$avg_data = mysqli_fetch_assoc($avg_result);
$avg_response_time = round($avg_data['avg_response_hours'] ?? 0);
mysqli_stmt_close($avg_stmt);

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'data' => $tickets,
    'total' => count($tickets),
    'total_all' => (int)$total_count,
    'has_more' => ($offset + $limit) < $total_count,
    'stats' => [
        'total' => (int)($counts['total'] ?? 0),
        'open' => (int)($counts['open'] ?? 0),
        'in_progress' => (int)($counts['in_progress'] ?? 0),
        'waiting' => (int)($counts['waiting'] ?? 0),
        'resolved' => (int)($counts['resolved'] ?? 0),
        'closed' => (int)($counts['closed'] ?? 0),
        'urgent' => (int)($counts['urgent'] ?? 0),
        'high' => (int)($counts['high'] ?? 0),
        'medium' => (int)($counts['medium'] ?? 0),
        'low' => (int)($counts['low'] ?? 0),
        'avg_response_time_hours' => $avg_response_time,
        'avg_response_time_formatted' => $avg_response_time . ' hour(s)'
    ],
    'categories' => $categories,
    'filters' => [
        'status' => $status_filter,
        'priority' => $priority_filter,
        'category' => $category_filter,
        'search' => $search,
        'limit' => $limit,
        'offset' => $offset
    ],
    'pagination' => [
        'current_page' => floor($offset / $limit) + 1,
        'per_page' => $limit,
        'total_pages' => ceil($total_count / $limit),
        'total_records' => (int)$total_count
    ]
]);

mysqli_close($conn);
?>