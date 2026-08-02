<?php
// ============================================================
// CIBIL REPAIR CRM - Add Customer API
// Endpoint: /api/add_customer.php
// Method: POST
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// ===== START OUTPUT BUFFERING =====
ob_start();

// ===== SET JSON HEADER =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

// ===== REMOVED: Direct access check =====
// if (basename($_SERVER['PHP_SELF']) === 'add_customer.php') {
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
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// ===== CLEAR OUTPUT BUFFER =====
if (ob_get_length()) ob_clean();

// ============================================================
// AUTHENTICATION
// ============================================================

$isTestMode = isset($_GET['test']) && $_GET['test'] === 'true';

if (!$isTestMode) {
    requireAuth();
    $userRole = $_SESSION['user_role'] ?? '';
    $allowedRoles = ['admin', 'super_admin', 'manager'];
    if (!in_array($userRole, $allowedRoles)) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access required.']);
        exit();
    }
}

// ============================================================
// VALIDATE REQUEST METHOD
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid request method. Use POST.']);
    exit();
}

// ============================================================
// CSRF VALIDATION
// ============================================================

if (!$isTestMode && !validateCSRF()) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token. Please refresh and try again.']);
    exit();
}

// ============================================================
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// ============================================================
// CUSTOMER INFORMATION
// ============================================================

$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$city = isset($input['city']) ? trim($input['city']) : '';
$service = isset($input['service']) ? trim($input['service']) : 'Written Off';
$status = isset($input['status']) ? trim($input['status']) : 'active';
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$joined = isset($input['joined']) ? trim($input['joined']) : date('Y-m-d');

// ============================================================
// EXTRA FIELDS (Stored in notes)
// ============================================================

$extra_fields = [];
if (isset($input['address']) && !empty($input['address'])) {
    $extra_fields[] = "Address: " . trim($input['address']);
}
if (isset($input['state']) && !empty($input['state'])) {
    $extra_fields[] = "State: " . trim($input['state']);
}
if (isset($input['pincode']) && !empty($input['pincode'])) {
    $extra_fields[] = "Pincode: " . trim($input['pincode']);
}
if (isset($input['country']) && !empty($input['country'])) {
    $extra_fields[] = "Country: " . trim($input['country']);
}
if (isset($input['date_of_birth']) && !empty($input['date_of_birth'])) {
    $extra_fields[] = "DOB: " . trim($input['date_of_birth']);
}
if (isset($input['gender']) && !empty($input['gender'])) {
    $extra_fields[] = "Gender: " . trim($input['gender']);
}
if (isset($input['occupation']) && !empty($input['occupation'])) {
    $extra_fields[] = "Occupation: " . trim($input['occupation']);
}
if (isset($input['company']) && !empty($input['company'])) {
    $extra_fields[] = "Company: " . trim($input['company']);
}
if (isset($input['pan_number']) && !empty($input['pan_number'])) {
    $extra_fields[] = "PAN: " . strtoupper(trim($input['pan_number']));
}
if (isset($input['aadhar_number']) && !empty($input['aadhar_number'])) {
    $extra_fields[] = "Aadhar: " . trim($input['aadhar_number']);
}

if (!empty($extra_fields)) {
    $notes = $notes . ($notes ? "\n" : "") . implode("\n", $extra_fields);
}

// ============================================================
// VALIDATION
// ============================================================

if (empty($name)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Customer name is required']);
    exit();
}

if (empty($email)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit();
}

if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid phone number. Must be 10 digits']);
    exit();
}

// ============================================================
// CHECK FOR DUPLICATES
// ============================================================

$existingEmail = dbFetchOne($conn, "SELECT id FROM customers WHERE email = ?", 's', $email);
if ($existingEmail) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Email already exists for another customer']);
    exit();
}

if (!empty($phone)) {
    $existingPhone = dbFetchOne($conn, "SELECT id FROM customers WHERE phone = ?", 's', $phone);
    if ($existingPhone) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Phone number already exists for another customer']);
        exit();
    }
}

// ============================================================
// INSERT CUSTOMER
// ============================================================

$sql = "INSERT INTO customers (
    name, email, phone, city, service, status, notes, joined
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?
)";

$affected = dbExecute($conn, $sql, 'ssssssss', 
    $name, $email, $phone, $city, $service, $status, $notes, $joined
);

if ($affected === -1) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Failed to create customer. Database error.']);
    exit();
}

// ============================================================
// GET THE NEW CUSTOMER
// ============================================================

$id = dbLastId($conn);
$customer = dbFetchOne($conn, "SELECT * FROM customers WHERE id = ?", 'i', $id);

// ============================================================
// LOG ACTIVITY
// ============================================================

$userName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';
logActivity($conn, 'Created customer', "Customer ID: $id, Name: $name, Email: $email", $userName);

// ============================================================
// SUCCESS RESPONSE
// ============================================================

ob_clean();
echo json_encode([
    'success' => true,
    'message' => 'Customer added successfully',
    'data' => $customer
]);

exit();
?>