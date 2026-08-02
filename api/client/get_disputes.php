<?php
// api/client/get_disputes.php - Get all disputes for client
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

// ========== CREATE DISPUTES TABLE IF NOT EXISTS ==========
$create_table = "CREATE TABLE IF NOT EXISTS disputes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    dispute_id VARCHAR(50) UNIQUE,
    bank_name VARCHAR(100) NOT NULL,
    issue_type VARCHAR(100) NOT NULL,
    account_number VARCHAR(50),
    amount DECIMAL(12,2),
    description TEXT,
    status ENUM('pending', 'in_progress', 'resolved', 'rejected', 'closed') DEFAULT 'pending',
    filed_date DATE,
    expected_resolution DATE,
    resolution_notes TEXT,
    resolved_date DATE,
    case_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_filed_date (filed_date)
)";

if (!mysqli_query($conn, $create_table)) {
    // Table might already exist, continue
}

// Generate unique dispute ID function
function generateDisputeId($conn, $client_id) {
    $prefix = 'DSP';
    $year = date('Y');
    $month = date('m');
    
    $query = "SELECT dispute_id FROM disputes WHERE dispute_id LIKE '$prefix$year$month%' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_num = (int)substr($row['dispute_id'], -4);
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    
    return $prefix . $year . $month . str_pad($new_num, 4, '0', STR_PAD_LEFT);
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Validate limit
if ($limit < 1 || $limit > 200) {
    $limit = 50;
}

// ========== BUILD QUERY ==========
$query = "SELECT 
            d.*,
            CASE 
                WHEN d.status = 'pending' THEN 0
                WHEN d.status = 'in_progress' THEN 50
                WHEN d.status = 'resolved' THEN 100
                WHEN d.status = 'rejected' THEN 100
                WHEN d.status = 'closed' THEN 100
                ELSE 0
            END as progress
          FROM disputes d
          WHERE d.client_id = ?";

$params = [$client_id];
$types = "i";

// Add status filter
if ($status_filter !== 'all') {
    $query .= " AND d.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " ORDER BY d.filed_date DESC, d.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$disputes = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== GET STATUS COUNTS ==========
$count_query = "SELECT 
                  COUNT(*) as total,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                  SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                  SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                  SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                  SUM(CASE WHEN status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as active
                FROM disputes WHERE client_id = ?";

$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $client_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$counts = mysqli_fetch_assoc($count_result);
mysqli_stmt_close($count_stmt);

// ========== GET TOTAL COUNT FOR PAGINATION ==========
$total_query = "SELECT COUNT(*) as total FROM disputes WHERE client_id = ?";
if ($status_filter !== 'all') {
    $total_query .= " AND status = ?";
}
$total_stmt = mysqli_prepare($conn, $total_query);
if ($status_filter !== 'all') {
    mysqli_stmt_bind_param($total_stmt, "is", $client_id, $status_filter);
} else {
    mysqli_stmt_bind_param($total_stmt, "i", $client_id);
}
mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_data = mysqli_fetch_assoc($total_result);
$total_count = $total_data['total'] ?? 0;
mysqli_stmt_close($total_stmt);

// ========== FORMAT DISPUTES ==========
$status_labels = [
    'pending' => 'Pending Review',
    'in_progress' => 'In Progress',
    'resolved' => 'Resolved',
    'rejected' => 'Rejected',
    'closed' => 'Closed'
];

$status_colors = [
    'pending' => 'warning',
    'in_progress' => 'info',
    'resolved' => 'success',
    'rejected' => 'danger',
    'closed' => 'secondary'
];

$issue_icons = [
    'Written Off Entry' => 'fa-ban',
    'Settled Entry' => 'fa-handshake',
    'Wrong Late Payment' => 'fa-calendar-times',
    'Duplicate Account' => 'fa-copy',
    'Incorrect Personal Info' => 'fa-id-card',
    'Fraudulent Loan' => 'fa-shield-alt',
    'Other Error' => 'fa-question-circle'
];

foreach ($disputes as &$d) {
    $d['status_label'] = $status_labels[$d['status']] ?? ucfirst($d['status']);
    $d['status_badge'] = $status_colors[$d['status']] ?? 'secondary';
    $d['issue_icon'] = $issue_icons[$d['issue_type']] ?? 'fa-gavel';
    $d['amount_formatted'] = $d['amount'] ? '₹' . number_format($d['amount'], 2) : '—';
    $d['filed_date_formatted'] = $d['filed_date'] ? date('d M Y', strtotime($d['filed_date'])) : date('d M Y', strtotime($d['created_at']));
    
    if ($d['expected_resolution']) {
        $d['expected_resolution_formatted'] = date('d M Y', strtotime($d['expected_resolution']));
        $days_left = ceil((strtotime($d['expected_resolution']) - time()) / 86400);
        $d['days_left'] = max(0, $days_left);
    }
    
    if ($d['resolved_date']) {
        $d['resolved_date_formatted'] = date('d M Y', strtotime($d['resolved_date']));
    }
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'data' => $disputes,
    'total' => count($disputes),
    'total_all' => (int)$total_count,
    'has_more' => ($offset + $limit) < $total_count,
    'stats' => [
        'total' => (int)($counts['total'] ?? 0),
        'pending' => (int)($counts['pending'] ?? 0),
        'in_progress' => (int)($counts['in_progress'] ?? 0),
        'resolved' => (int)($counts['resolved'] ?? 0),
        'rejected' => (int)($counts['rejected'] ?? 0),
        'active' => (int)($counts['active'] ?? 0)
    ],
    'filters' => [
        'status' => $status_filter,
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