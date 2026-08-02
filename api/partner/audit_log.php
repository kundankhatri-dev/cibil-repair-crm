<?php
// api/partner/audit_log.php
// Complete audit trail of all actions

session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$partner_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'view';

// Create audit table if not exists
$auditTable = 'audit_logs';
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE '$auditTable'");
if (mysqli_num_rows($checkTable) == 0) {
    $createTable = "CREATE TABLE $auditTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(100),
        entity_type VARCHAR(50),
        entity_id INT,
        old_values TEXT,
        new_values TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_action (action),
        INDEX idx_created (created_at)
    )";
    mysqli_query($conn, $createTable);
}

// Function to log actions (call this from other APIs)
function logAudit($conn, $user_id, $action, $entity_type, $entity_id, $old_values = null, $new_values = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ississss", $user_id, $action, $entity_type, $entity_id, $old_values, $new_values, $ip, $user_agent);
    return mysqli_stmt_execute($stmt);
}

if ($action === 'view') {
    $limit = $_GET['limit'] ?? 50;
    $page = $_GET['page'] ?? 1;
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT * FROM $auditTable WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iii", $partner_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $logs = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // Get count
    $count_query = "SELECT COUNT(*) as total FROM $auditTable WHERE user_id = ?";
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, "i", $partner_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total = mysqli_fetch_assoc($count_result)['total'] ?? 0;
    
    // Action type icons
    $action_icons = [
        'login' => 'fa-sign-in-alt',
        'logout' => 'fa-sign-out-alt',
        'add_lead' => 'fa-plus-circle',
        'update_lead' => 'fa-edit',
        'delete_lead' => 'fa-trash',
        'request_payout' => 'fa-money-bill-wave',
        'create_ticket' => 'fa-ticket-alt',
        'update_profile' => 'fa-user-edit'
    ];
    
    foreach ($logs as &$log) {
        $log['icon'] = $action_icons[$log['action']] ?? 'fa-history';
        $log['formatted_date'] = date('d M Y, h:i A', strtotime($log['created_at']));
        $log['time_ago'] = timeAgo(strtotime($log['created_at']));
    }
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

function timeAgo($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('d M Y', $timestamp);
}

mysqli_close($conn);
?>