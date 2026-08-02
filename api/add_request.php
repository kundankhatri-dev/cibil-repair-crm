<?php
// ============================================================
// CIBIL REPAIR CRM - Add Customer Request API
// Endpoint: /api/add_request.php
// Method: POST
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADER =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== REMOVED: Direct access check =====
// if (basename($_SERVER['PHP_SELF']) === 'add_request.php') {
//     http_response_code(403);
//     exit('Direct access forbidden.');
// }

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ===== INCLUDE DATABASE =====
require_once __DIR__ . '/db.php';

// ===== CHECK CONNECTION =====
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ============================================================
// GET INPUT DATA
// ============================================================

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input) {
    $input = $_POST;
}

// ============================================================
// REQUEST INFORMATION
// ============================================================

$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$service = isset($input['service']) ? trim($input['service']) : 'Written Off';
$date = isset($input['date']) ? trim($input['date']) : date('Y-m-d');
$status = isset($input['status']) ? trim($input['status']) : 'pending';
$priority = isset($input['priority']) ? trim($input['priority']) : 'medium';
$request_type = isset($input['request_type']) ? trim($input['request_type']) : 'general';
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$assigned_to = isset($input['assigned_to']) ? intval($input['assigned_to']) : 0;
$source = isset($input['source']) ? trim($input['source']) : 'website';
$follow_up_date = isset($input['follow_up_date']) ? trim($input['follow_up_date']) : '';
$customer_id = isset($input['customer_id']) ? intval($input['customer_id']) : 0;

// ============================================================
// VALIDATION
// ============================================================

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Name is required']);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
    echo json_encode(['success' => false, 'error' => 'Invalid phone number. Must be 10 digits']);
    exit;
}

$allowedPriority = ['low', 'medium', 'high', 'urgent'];
if (!in_array($priority, $allowedPriority)) {
    echo json_encode(['success' => false, 'error' => 'Invalid priority. Allowed: low, medium, high, urgent']);
    exit;
}

$allowedRequestTypes = ['general', 'support', 'complaint', 'query', 'feedback', 'other'];
if (!in_array($request_type, $allowedRequestTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request type']);
    exit;
}

$allowedStatus = ['pending', 'approved', 'rejected', 'in_progress', 'completed'];
if (!in_array($status, $allowedStatus)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status. Allowed: pending, approved, rejected, in_progress, completed']);
    exit;
}

// ============================================================
// CREATE TABLE IF NOT EXISTS
// ============================================================

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'customer_requests'");
if (mysqli_num_rows($tableCheck) == 0) {
    $createTable = "CREATE TABLE customer_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        phone VARCHAR(20),
        service VARCHAR(100),
        date DATE,
        status VARCHAR(50) DEFAULT 'pending',
        priority VARCHAR(20) DEFAULT 'medium',
        request_type VARCHAR(50) DEFAULT 'general',
        notes TEXT,
        assigned_to INT DEFAULT 0,
        source VARCHAR(50) DEFAULT 'website',
        follow_up_date DATE,
        customer_id INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $createTable);
}

// ============================================================
// INSERT REQUEST
// ============================================================

$name = mysqli_real_escape_string($conn, $name);
$email = mysqli_real_escape_string($conn, $email);
$phone = mysqli_real_escape_string($conn, $phone);
$service = mysqli_real_escape_string($conn, $service);
$date = mysqli_real_escape_string($conn, $date);
$status = mysqli_real_escape_string($conn, $status);
$priority = mysqli_real_escape_string($conn, $priority);
$request_type = mysqli_real_escape_string($conn, $request_type);
$notes = mysqli_real_escape_string($conn, $notes);
$source = mysqli_real_escape_string($conn, $source);
$follow_up_date = mysqli_real_escape_string($conn, $follow_up_date);

$sql = "INSERT INTO customer_requests (
    name, email, phone, service, date, status, priority,
    request_type, notes, assigned_to, source, follow_up_date,
    customer_id
) VALUES (
    '$name', '$email', '$phone', '$service', '$date', '$status', '$priority',
    '$request_type', '$notes', $assigned_to, '$source', '$follow_up_date',
    $customer_id
)";

$result = mysqli_query($conn, $sql);

if ($result) {
    $id = mysqli_insert_id($conn);
    
    // Get the inserted request
    $result2 = mysqli_query($conn, "SELECT * FROM customer_requests WHERE id = $id");
    $request = mysqli_fetch_assoc($result2);
    
    echo json_encode([
        'success' => true,
        'message' => 'Request added successfully',
        'data' => $request
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
exit;
?>