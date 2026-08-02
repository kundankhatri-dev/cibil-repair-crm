<?php
// /api/email/history.php
header('Content-Type: application/json');

try {
    require_once '../../config/database.php';
    global $conn;
    
    if (!isset($conn) || !$conn instanceof mysqli) {
        if (defined('DB_HOST')) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception('Connection failed: ' . $conn->connect_error);
            }
        } else {
            throw new Exception('Database connection not available');
        }
    }
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
    
    $sql = "SELECT * FROM email_history";
    $params = [];
    $types = "";
    $where = [];
    
    // Add filters
    if (!empty($status) && $status !== 'all') {
        $where[] = "status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($search)) {
        $searchParam = "%{$search}%";
        $where[] = "(recipient_email LIKE ? OR subject LIKE ? OR template_key LIKE ?)";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }
    
    if (!empty($date_from)) {
        $where[] = "sent_at >= ?";
        $params[] = $date_from . " 00:00:00";
        $types .= "s";
    }
    
    if (!empty($date_to)) {
        $where[] = "sent_at <= ?";
        $params[] = $date_to . " 23:59:59";
        $types .= "s";
    }
    
    if (count($where) > 0) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    // Get total count
    $countSql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
    $countStmt = $conn->prepare($countSql);
    
    if (count($params) > 0) {
        $countStmt->bind_param($types, ...$params);
    }
    
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // Get paginated data
    $sql .= " ORDER BY sent_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        // Format dates
        if (isset($row['sent_at'])) {
            $row['formatted_date'] = date('d M Y, h:i A', strtotime($row['sent_at']));
        }
        if (isset($row['created_at'])) {
            $row['created_date'] = date('d M Y, h:i A', strtotime($row['created_at']));
        }
        
        // Truncate long messages
        if (isset($row['message']) && strlen($row['message']) > 200) {
            $row['message_preview'] = substr($row['message'], 0, 200) . '...';
        } else {
            $row['message_preview'] = $row['message'] ?? '';
        }
        
        $history[] = $row;
    }
    
    $stmt->close();
    
    // Get summary stats
    $statsResult = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM email_history");
    $stats = $statsResult->fetch_assoc();
    
    // Get unique templates used
    $templatesResult = $conn->query("SELECT DISTINCT template_key FROM email_history WHERE template_key IS NOT NULL AND template_key != ''");
    $templates = [];
    while ($row = $templatesResult->fetch_assoc()) {
        $templates[] = $row['template_key'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $history,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset,
        'stats' => [
            'total' => (int)$stats['total'],
            'sent' => (int)$stats['sent'],
            'failed' => (int)$stats['failed'],
            'pending' => (int)$stats['pending']
        ],
        'templates' => $templates,
        'filters' => [
            'status' => $status,
            'search' => $search,
            'date_from' => $date_from,
            'date_to' => $date_to
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit;
?>