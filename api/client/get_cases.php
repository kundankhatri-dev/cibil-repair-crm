<?php
// api/client/get_cases.php - Get all cases for client
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

// Get filter parameters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Validate limit
if ($limit < 1 || $limit > 200) {
    $limit = 50;
}

// ========== CHECK WHICH TABLE TO USE ==========
$has_client_cases = mysqli_query($conn, "SHOW TABLES LIKE 'client_cases'");
$use_client_cases = mysqli_num_rows($has_client_cases) > 0;

// ========== BUILD QUERY ==========
if ($use_client_cases) {
    // Using client_cases table
    $query = "SELECT 
                c.id,
                c.case_no,
                c.service_type,
                c.status,
                c.amount,
                c.created_at,
                c.updated_at,
                c.progress,
                CASE 
                    WHEN c.status = 'pending' THEN 0
                    WHEN c.status = 'processing' THEN 20
                    WHEN c.status = 'document_verification' THEN 40
                    WHEN c.status = 'dispute_filed' THEN 60
                    WHEN c.status = 'bank_response' THEN 80
                    WHEN c.status IN ('resolved', 'closed') THEN 100
                    ELSE 0
                END as progress_percent,
                l.customer_name,
                l.customer_phone
              FROM client_cases c
              LEFT JOIN leads l ON c.lead_id = l.id
              WHERE c.client_id = ?";
    
    $params = [$client_id];
    $types = "i";
    
    // Add status filter
    if ($status_filter !== 'all') {
        $query .= " AND c.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (c.case_no LIKE ? OR c.service_type LIKE ? OR l.customer_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    $query .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
} else {
    // Fallback to leads table
    $query = "SELECT 
                l.id,
                l.id as case_no,
                l.service_type,
                l.status,
                l.commission_amount as amount,
                l.created_at,
                l.updated_at,
                l.customer_name,
                l.customer_phone,
                CASE 
                    WHEN l.status = 'converted' THEN 100
                    WHEN l.status = 'contacted' THEN 50
                    WHEN l.status = 'new' THEN 10
                    ELSE 0
                END as progress_percent
              FROM leads l
              WHERE l.customer_id = ?";
    
    $params = [$client_id];
    $types = "i";
    
    // Add status filter
    if ($status_filter !== 'all') {
        $query .= " AND l.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (l.service_type LIKE ? OR l.customer_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    $query .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
}

// ========== EXECUTE QUERY ==========
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cases = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ========== GET STATUS COUNTS ==========
$count_query = "SELECT 
                  COUNT(*) as total,
                  SUM(CASE WHEN status IN ('pending', 'processing', 'document_verification', 'dispute_filed', 'bank_response', 'new', 'contacted') THEN 1 ELSE 0 END) as active,
                  SUM(CASE WHEN status IN ('resolved', 'closed', 'converted') THEN 1 ELSE 0 END) as completed,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                FROM " . ($use_client_cases ? "client_cases" : "leads") . " 
                WHERE client_id = ?";

$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $client_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$counts = mysqli_fetch_assoc($count_result);
mysqli_stmt_close($count_stmt);

// ========== GET TOTAL COUNT FOR PAGINATION ==========
$total_query = "SELECT COUNT(*) as total FROM " . ($use_client_cases ? "client_cases" : "leads") . " WHERE client_id = ?";
if ($status_filter !== 'all') {
    $total_query .= " AND status = ?";
}
if (!empty($search)) {
    $total_query .= " AND (service_type LIKE ? OR customer_name LIKE ?)";
}

$total_stmt = mysqli_prepare($conn, $total_query);
if ($status_filter !== 'all' && !empty($search)) {
    $search_param = "%$search%";
    mysqli_stmt_bind_param($total_stmt, "isss", $client_id, $status_filter, $search_param, $search_param);
} elseif ($status_filter !== 'all') {
    mysqli_stmt_bind_param($total_stmt, "is", $client_id, $status_filter);
} elseif (!empty($search)) {
    $search_param = "%$search%";
    mysqli_stmt_bind_param($total_stmt, "iss", $client_id, $search_param, $search_param);
} else {
    mysqli_stmt_bind_param($total_stmt, "i", $client_id);
}

mysqli_stmt_execute($total_stmt);
$total_result = mysqli_stmt_get_result($total_stmt);
$total_data = mysqli_fetch_assoc($total_result);
$total_count = $total_data['total'] ?? 0;
mysqli_stmt_close($total_stmt);

// ========== FORMAT CASES ==========
$status_labels = [
    'pending' => 'Pending', 'processing' => 'Processing',
    'document_verification' => 'Document Verification',
    'dispute_filed' => 'Dispute Filed', 'bank_response' => 'Bank Response',
    'resolved' => 'Resolved', 'closed' => 'Closed',
    'new' => 'New', 'contacted' => 'Contacted', 'converted' => 'Converted'
];

$status_colors = [
    'pending' => 'warning', 'processing' => 'info',
    'document_verification' => 'info', 'dispute_filed' => 'primary',
    'bank_response' => 'warning', 'resolved' => 'success',
    'closed' => 'secondary', 'new' => 'info',
    'contacted' => 'warning', 'converted' => 'success'
];

foreach ($cases as &$case) {
    $case['amount'] = (float)($case['amount'] ?? 0);
    $case['amount_formatted'] = '₹' . number_format($case['amount'], 2);
    $case['status_label'] = $status_labels[$case['status']] ?? ucfirst($case['status']);
    $case['status_badge'] = $status_colors[$case['status']] ?? 'secondary';
    $case['created_date'] = date('d M Y', strtotime($case['created_at']));
    $case['created_time'] = date('h:i A', strtotime($case['created_at']));
    
    if ($case['updated_at']) {
        $case['updated_date'] = date('d M Y', strtotime($case['updated_at']));
    }
    
    // Ensure progress is set
    if (!isset($case['progress']) && !isset($case['progress_percent'])) {
        $progress_map = [
            'pending' => 0, 'new' => 5,
            'processing' => 20, 'contacted' => 30,
            'document_verification' => 40, 'dispute_filed' => 60,
            'bank_response' => 80, 'converted' => 100,
            'resolved' => 100, 'closed' => 100
        ];
        $case['progress'] = $progress_map[$case['status']] ?? 10;
    } else {
        $case['progress'] = $case['progress'] ?? $case['progress_percent'];
    }
}

// ========== RETURN RESPONSE ==========
echo json_encode([
    'success' => true,
    'data' => $cases,
    'total' => count($cases),
    'total_all' => (int)$total_count,
    'has_more' => ($offset + $limit) < $total_count,
    'stats' => [
        'total' => (int)($counts['total'] ?? 0),
        'active' => (int)($counts['active'] ?? 0),
        'completed' => (int)($counts['completed'] ?? 0),
        'pending' => (int)($counts['pending'] ?? 0)
    ],
    'filters' => [
        'status' => $status_filter,
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