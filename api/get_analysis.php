<?php
// ============================================================
// CIBIL REPAIR CRM - Get Analysis by ID API
// Endpoint: /api/get_analysis.php
// Method: GET
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

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

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
// GET ID PARAMETER
// ============================================================

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid ID. Please provide a valid analysis ID.']);
    mysqli_close($conn);
    exit;
}

// ============================================================
// CHECK IF TABLE EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'document_analyses'");
if (mysqli_num_rows($tableCheck) == 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Document analyses table not found']);
    mysqli_close($conn);
    exit;
}

// ============================================================
// GET ACTUAL COLUMNS
// ============================================================

$columns = [];
$columnResult = mysqli_query($conn, "SHOW COLUMNS FROM document_analyses");
if ($columnResult) {
    while ($row = mysqli_fetch_assoc($columnResult)) {
        $columns[] = $row['Field'];
    }
    mysqli_free_result($columnResult);
}

// ============================================================
// BUILD SELECT CLAUSE
// ============================================================

// Always include these basic columns
$selectColumns = ['id', 'document_type', 'filename', 'analysis_result', 'status', 'created_at'];

// Add optional columns if they exist
$optionalColumns = ['dispute_letter', 'guidance', 'file_path', 'file_size', 'user_id'];
foreach ($optionalColumns as $col) {
    if (in_array($col, $columns)) {
        $selectColumns[] = $col;
    }
}

$selectClause = implode(', ', $selectColumns);

// ============================================================
// EXECUTE QUERY
// ============================================================

try {
    $query = "SELECT $selectClause FROM document_analyses WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Format response
        $response = [
            'success' => true,
            'message' => 'Analysis found',
            'data' => [
                'id' => (int)$row['id'],
                'document_type' => $row['document_type'] ?? '',
                'filename' => $row['filename'] ?? '',
                'analysis_result' => $row['analysis_result'] ?? '',
                'status' => $row['status'] ?? 'completed',
                'created_at' => $row['created_at'] ?? ''
            ]
        ];
        
        // Add optional fields if they exist
        if (isset($row['dispute_letter'])) {
            $response['data']['dispute_letter'] = $row['dispute_letter'] ?? '';
        }
        if (isset($row['guidance'])) {
            $response['data']['guidance'] = $row['guidance'] ?? '';
        }
        if (isset($row['file_path'])) {
            $response['data']['file_path'] = $row['file_path'] ?? '';
        }
        if (isset($row['file_size'])) {
            $response['data']['file_size'] = $row['file_size'] ?? '';
        }
        if (isset($row['user_id'])) {
            $response['data']['user_id'] = (int)$row['user_id'];
        }
        
        echo json_encode($response);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Analysis not found']);
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
exit;
?>