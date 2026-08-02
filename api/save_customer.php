<?php
// ============================================================
// CIBIL REPAIR CRM - Save Customer API (Add or Update)
// Endpoint: /api/save_customer.php
// Method: POST
// ============================================================

// Include database helpers
require_once __DIR__ . '/db.php';

// ============================================================
// CORS & HEADERS
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// AUTHENTICATION
// ============================================================

// requireAuth();

$userRole = $_SESSION['user_role'] ?? '';
$allowedRoles = ['admin', 'super_admin', 'manager'];
$isTestMode = isset($_GET['test']) && $_GET['test'] === 'true';
// if (!$isTestMode && !in_array($userRole, $allowedRoles)) {
//     jsonResponse(false, 'Unauthorized. Admin access required.', null, 403);
// }

// ============================================================
// VALIDATE REQUEST METHOD
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method. Use POST.', null, 405);
}

// ============================================================
// CSRF VALIDATION (DISABLED FOR TESTING)
// ============================================================

// if (!$isTestMode && !validateCSRF()) {
//     jsonResponse(false, 'Invalid CSRF token. Please refresh and try again.', null, 403);
// }

// ============================================================
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// ============================================================
// CUSTOMER INFORMATION (Matches Database Schema)
// ============================================================

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$city = isset($input['city']) ? trim($input['city']) : '';
$service = isset($input['service']) ? trim($input['service']) : 'Written Off';
$status = isset($input['status']) ? trim($input['status']) : 'active';
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$joined = isset($input['joined']) ? trim($input['joined']) : date('Y-m-d');

// ============================================================
// EXTRA FIELDS (Optional - stored in notes)
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
    jsonResponse(false, 'Customer name is required');
    return;
}

if (empty($email)) {
    jsonResponse(false, 'Email is required');
    return;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format');
    return;
}

if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
    jsonResponse(false, 'Invalid phone number. Must be 10 digits');
    return;
}

// ============================================================
// CHECK FOR DUPLICATES (Exclude current record for update)
// ============================================================

// Check email duplicate
if ($id > 0) {
    $existing = dbFetchOne($conn, "SELECT id FROM customers WHERE email = ? AND id != ?", 'si', $email, $id);
} else {
    $existing = dbFetchOne($conn, "SELECT id FROM customers WHERE email = ?", 's', $email);
}
if ($existing) {
    jsonResponse(false, 'Email already exists for another customer');
    return;
}

// Check phone duplicate (if phone is provided)
if (!empty($phone)) {
    if ($id > 0) {
        $existingPhone = dbFetchOne($conn, "SELECT id FROM customers WHERE phone = ? AND id != ?", 'si', $phone, $id);
    } else {
        $existingPhone = dbFetchOne($conn, "SELECT id FROM customers WHERE phone = ?", 's', $phone);
    }
    if ($existingPhone) {
        jsonResponse(false, 'Phone number already exists for another customer');
        return;
    }
}

// ============================================================
// SAVE CUSTOMER (INSERT OR UPDATE)
// ============================================================

if ($id > 0) {
    // UPDATE existing customer
    $sql = "UPDATE customers SET name=?, email=?, phone=?, city=?, service=?, status=?, notes=? WHERE id=?";
    $affected = dbExecute($conn, $sql, 'sssssssi', $name, $email, $phone, $city, $service, $status, $notes, $id);
    
    if ($affected !== -1) {
        logActivity($conn, 'Updated customer', "Customer ID: $id, Name: $name");
        $customer = dbFetchOne($conn, "SELECT * FROM customers WHERE id = ?", 'i', $id);
        jsonResponse(true, 'Customer updated successfully', ['customer' => $customer]);
    } else {
        jsonResponse(false, 'Failed to update customer', null, 500);
    }
} else {
    // INSERT new customer
    $sql = "INSERT INTO customers (name, email, phone, city, service, status, notes, joined) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $affected = dbExecute($conn, $sql, 'ssssssss', $name, $email, $phone, $city, $service, $status, $notes, $joined);
    
    if ($affected !== -1) {
        $id = dbLastId($conn);
        logActivity($conn, 'Created customer', "Customer ID: $id, Name: $name");
        $customer = dbFetchOne($conn, "SELECT * FROM customers WHERE id = ?", 'i', $id);
        jsonResponse(true, 'Customer created successfully', ['customer' => $customer]);
    } else {
        jsonResponse(false, 'Failed to create customer', null, 500);
    }
}

// ============================================================
// CLOSE CONNECTION
// ============================================================

// The database connection is managed by db.php
?>