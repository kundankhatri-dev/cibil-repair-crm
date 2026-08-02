<?php
// ============================================================
// CIBIL REPAIR CRM - Get Client Documents API
// ============================================================

error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

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
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
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
// GET PARAMETERS
// ============================================================

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

// ============================================================
// VALIDATE - Return empty data if no email or client_id
// ============================================================

if (empty($email) && $clientId <= 0) {
    echo json_encode([
        'success' => true,
        'data' => [
            'documents' => [],
            'total' => 0,
            'limit' => $limit,
            'offset' => $offset,
            'message' => 'No email or client_id provided'
        ]
    ]);
    mysqli_close($conn);
    exit;
}

// ============================================================
// CHECK IF TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'client_documents'");
if (mysqli_num_rows($tableCheck) == 0) {
    echo json_encode([
        'success' => true,
        'data' => [
            'documents' => [],
            'total' => 0,
            'message' => 'No documents found'
        ]
    ]);
    mysqli_close($conn);
    exit;
}

// ============================================================
// BUILD QUERY
// ============================================================

$where = [];

if (!empty($email)) {
    $where[] = "client_email = '" . mysqli_real_escape_string($conn, $email) . "'";
}

if ($clientId > 0) {
    $where[] = "client_id = " . intval($clientId);
}

$whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// ============================================================
// GET DOCUMENTS
// ============================================================

try {
    // Get documents
    $query = "SELECT id, client_email, doc_type, file_name, file_path, file_type, uploaded_at 
              FROM client_documents 
              $whereClause 
              ORDER BY uploaded_at DESC 
              LIMIT $limit OFFSET $offset";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }
    
    $documents = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $documents[] = [
            'id' => (int)$row['id'],
            'client_email' => $row['client_email'] ?? '',
            'doc_type' => $row['doc_type'] ?? '',
            'file_name' => $row['file_name'] ?? '',
            'file_path' => $row['file_path'] ?? '',
            'file_type' => $row['file_type'] ?? '',
            'uploaded_at' => $row['uploaded_at'] ?? ''
        ];
    }
    mysqli_free_result($result);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM client_documents $whereClause";
    $countResult = mysqli_query($conn, $countQuery);
    $total = $countResult ? (int)mysqli_fetch_assoc($countResult)['total'] : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'documents' => $documents,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
exit;
?>