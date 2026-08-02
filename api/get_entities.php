<?php
// ============================================================
// CIBIL REPAIR CRM - Get Entities API (COMPLETE FIX)
// ============================================================

// Disable error display
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
    exit;
}

$userRole = $_SESSION['user_role'] ?? '';

// Check admin role
if (!in_array($userRole, ['admin', 'super_admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden. Admin access required.']);
    exit;
}

// Database connection
$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// ============================================================
// CREATE TABLE IF NOT EXISTS
// ============================================================

$createTable = "
CREATE TABLE IF NOT EXISTS banks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_status (status),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";
mysqli_query($conn, $createTable);

// ============================================================
// ENTITY TYPE DEFINITIONS
// ============================================================

$entityTypes = [
    'bank' => 'Bank',
    'lawyer' => 'Law Firm / Advocate',
    'ca' => 'Chartered Accountant',
    'franchise' => 'Franchise Store',
    'real_estate' => 'Real Estate Agent',
    'insurance' => 'Insurance Agent',
    'consultant' => 'Business Consultant',
    'agency' => 'Recruitment Agency',
    'broker' => 'Broker / Agent',
    'other' => 'Other'
];

// ============================================================
// GET PARAMETERS
// ============================================================

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, trim($_GET['status'])) : '';
$entityType = isset($_GET['entity_type']) ? mysqli_real_escape_string($conn, trim($_GET['entity_type'])) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$sort_by = isset($_GET['sort_by']) ? mysqli_real_escape_string($conn, trim($_GET['sort_by'])) : 'id';
$sort_order = isset($_GET['sort_order']) ? mysqli_real_escape_string($conn, trim($_GET['sort_order'])) : 'DESC';

// Validate
if ($limit < 1 || $limit > 500) $limit = 100;
if ($offset < 0) $offset = 0;
$sort_order = strtoupper($sort_order);
if (!in_array($sort_order, ['ASC', 'DESC'])) $sort_order = 'DESC';

// ============================================================
// GET SINGLE ENTITY
// ============================================================

if ($id > 0) {
    $query = "SELECT * FROM banks WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $entity = mysqli_fetch_assoc($result);
    
    if ($entity) {
        // Parse entity type
        $entityTypeLabel = 'Other';
        if (!empty($entity['notes']) && preg_match('/Entity Type: (.+)/', $entity['notes'], $matches)) {
            $entityTypeLabel = trim($matches[1]);
        }
        $entity['entity_type_label'] = $entityTypeLabel;
        
        echo json_encode(['success' => true, 'data' => $entity]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Entity not found']);
    }
    mysqli_close($conn);
    exit;
}

// ============================================================
// BUILD QUERY - Simple string concatenation
// ============================================================

$where = array();

// Search filter
if (!empty($search)) {
    $where[] = "(name LIKE '%$search%' OR contact LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}

// Status filter
if (!empty($status)) {
    $where[] = "status = '$status'";
}

// Entity type filter
if (!empty($entityType) && isset($entityTypes[$entityType])) {
    $label = $entityTypes[$entityType];
    $where[] = "notes LIKE '%Entity Type: $label%'";
}

// Build WHERE clause
$whereClause = "";
if (!empty($where)) {
    $whereClause = " WHERE " . implode(" AND ", $where);
}

// ============================================================
// GET ENTITIES
// ============================================================

$query = "SELECT id, name, contact, email, phone, status, notes, created_at FROM banks $whereClause ORDER BY $sort_by $sort_order LIMIT $limit OFFSET $offset";

$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Query failed: ' . mysqli_error($conn)]);
    mysqli_close($conn);
    exit;
}

$entities = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Parse entity type
    $entityTypeLabel = 'Other';
    $entityTypeKey = 'other';
    if (!empty($row['notes']) && preg_match('/Entity Type: (.+)/', $row['notes'], $matches)) {
        $entityTypeLabel = trim($matches[1]);
        $entityTypeKey = array_search($entityTypeLabel, $entityTypes);
        if ($entityTypeKey === false) {
            $entityTypeKey = 'other';
        }
    }
    
    $row['entity_type'] = $entityTypeKey;
    $row['entity_type_label'] = $entityTypeLabel;
    $entities[] = $row;
}

// ============================================================
// GET TOTAL COUNT
// ============================================================

$countQuery = "SELECT COUNT(*) as total FROM banks $whereClause";
$countResult = mysqli_query($conn, $countQuery);
$total = $countResult ? (int)mysqli_fetch_assoc($countResult)['total'] : 0;

// ============================================================
// GET STATUS COUNTS
// ============================================================

$statusCounts = ['total' => $total, 'active' => 0, 'inactive' => 0, 'suspended' => 0];
$statusResult = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM banks GROUP BY status");
if ($statusResult) {
    while ($row = mysqli_fetch_assoc($statusResult)) {
        if (isset($statusCounts[$row['status']])) {
            $statusCounts[$row['status']] = (int)$row['count'];
        }
    }
    mysqli_free_result($statusResult);
}

// ============================================================
// GET ENTITY TYPE COUNTS
// ============================================================

$entityCounts = [];
foreach ($entityTypes as $key => $label) {
    $countResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM banks WHERE notes LIKE '%Entity Type: $label%'");
    if ($countResult) {
        $row = mysqli_fetch_assoc($countResult);
        $entityCounts[$key] = (int)$row['count'];
        mysqli_free_result($countResult);
    } else {
        $entityCounts[$key] = 0;
    }
}

// ============================================================
// RESPONSE
// ============================================================

echo json_encode([
    'success' => true,
    'message' => 'Entities retrieved successfully',
    'data' => [
        'entities' => $entities,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'filters' => [
            'search' => $search,
            'status' => $status,
            'entity_type' => $entityType
        ],
        'sort' => [
            'by' => $sort_by,
            'order' => $sort_order
        ],
        'status_counts' => $statusCounts,
        'entity_counts' => $entityCounts
    ]
]);

mysqli_close($conn);
exit;
?>