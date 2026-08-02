<?php
// ============================================================
// CIBIL REPAIR CRM - Update Customer Request API
// Endpoint: /api/update_request.php
// Method: POST, PUT
// ============================================================

// Include database helpers
require_once __DIR__ . '/db.php';

// ============================================================
// CORS & HEADERS
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('X-Content-Type-Options: nosniff');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// AUTHENTICATION (DISABLED FOR TESTING)
// ============================================================

// requireAuth();

// $userRole = $_SESSION['user_role'] ?? '';
// $allowedRoles = ['admin', 'super_admin', 'manager'];
$isTestMode = isset($_GET['test']) && $_GET['test'] === 'true';

// if (!$isTestMode && !in_array($userRole, $allowedRoles)) {
//     jsonResponse(false, 'Unauthorized. Admin access required.', null, 403);
// }

// ============================================================
// VALIDATE REQUEST METHOD
// ============================================================

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    jsonResponse(false, 'Invalid request method. Use POST or PUT.', null, 405);
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
// REQUEST INFORMATION
// ============================================================

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$service = isset($input['service']) ? trim($input['service']) : '';
$date = isset($input['date']) ? trim($input['date']) : date('Y-m-d');
$status = isset($input['status']) ? trim($input['status']) : '';
$priority = isset($input['priority']) ? trim($input['priority']) : '';
$request_type = isset($input['request_type']) ? trim($input['request_type']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$assigned_to = isset($input['assigned_to']) ? intval($input['assigned_to']) : 0;
$source = isset($input['source']) ? trim($input['source']) : '';
$follow_up_date = isset($input['follow_up_date']) ? trim($input['follow_up_date']) : '';
$customer_id = isset($input['customer_id']) ? intval($input['customer_id']) : 0;
$resolved_date = isset($input['resolved_date']) ? trim($input['resolved_date']) : '';
$resolution_notes = isset($input['resolution_notes']) ? trim($input['resolution_notes']) : '';

// ============================================================
# VALIDATION
// ============================================================

if (!$id) {
    jsonResponse(false, 'Request ID is required', null, 400);
    return;
}

if (empty($name)) {
    jsonResponse(false, 'Name is required');
    return;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format');
    return;
}

if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
    jsonResponse(false, 'Invalid phone number. Must be 10 digits');
    return;
}

// Validate priority if provided
$allowedPriority = ['low', 'medium', 'high', 'urgent'];
if (!empty($priority) && !in_array($priority, $allowedPriority)) {
    jsonResponse(false, 'Invalid priority. Allowed: low, medium, high, urgent');
    return;
}

// Validate request type if provided
$allowedRequestTypes = ['general', 'support', 'complaint', 'query', 'feedback', 'other'];
if (!empty($request_type) && !in_array($request_type, $allowedRequestTypes)) {
    jsonResponse(false, 'Invalid request type');
    return;
}

// Validate status if provided
$allowedStatus = ['pending', 'approved', 'rejected', 'in_progress', 'completed'];
if (!empty($status) && !in_array($status, $allowedStatus)) {
    jsonResponse(false, 'Invalid status. Allowed: pending, approved, rejected, in_progress, completed');
    return;
}

// Validate source if provided
$allowedSources = ['website', 'email', 'phone', 'whatsapp', 'facebook', 'instagram', 'referral', 'walk_in', 'other'];
if (!empty($source) && !in_array($source, $allowedSources)) {
    jsonResponse(false, 'Invalid source');
    return;
}

// Validate follow-up date if provided
if (!empty($follow_up_date) && !strtotime($follow_up_date)) {
    jsonResponse(false, 'Invalid follow-up date format');
    return;
}

// Validate resolved date if provided
if (!empty($resolved_date) && !strtotime($resolved_date)) {
    jsonResponse(false, 'Invalid resolved date format');
    return;
}

// ============================================================
// CHECK IF REQUEST EXISTS
// ============================================================

$existingRequest = dbFetchOne($conn, "SELECT * FROM customer_requests WHERE id = ?", 'i', $id);
if (!$existingRequest) {
    jsonResponse(false, 'Request not found', null, 404);
    return;
}

// ============================================================
// CHECK FOR DUPLICATE REQUEST (Exclude current)
// ============================================================

if (!empty($date)) {
    $duplicate = dbFetchOne($conn, 
        "SELECT id FROM customer_requests WHERE name = ? AND service = ? AND date = ? AND id != ?", 
        'sssi', $name, $service, $date, $id
    );
    if ($duplicate) {
        jsonResponse(false, 'Duplicate request found');
        return;
    }
}

// ============================================================
// GET CUSTOMER ID IF NOT PROVIDED
// ============================================================

if ($customer_id == 0 && !empty($email)) {
    $customer = dbFetchOne($conn, "SELECT id FROM customers WHERE email = ?", 's', $email);
    if ($customer) {
        $customer_id = $customer['id'];
    }
}

// ============================================================
// BUILD UPDATE FIELDS
// ============================================================

$updates = [];
$params = [];
$types = '';

$fieldMap = [
    'name' => 's',
    'email' => 's',
    'phone' => 's',
    'service' => 's',
    'date' => 's',
    'status' => 's',
    'priority' => 's',
    'request_type' => 's',
    'notes' => 's',
    'assigned_to' => 'i',
    'source' => 's',
    'follow_up_date' => 's',
    'customer_id' => 'i',
    'resolved_date' => 's',
    'resolution_notes' => 's'
];

foreach ($fieldMap as $field => $type) {
    if (isset($input[$field]) && $input[$field] !== '') {
        $value = $input[$field];
        if ($field === 'assigned_to' || $field === 'customer_id') {
            $value = intval($value);
        } else {
            $value = trim($value);
        }
        $updates[] = "$field = ?";
        $params[] = $value;
        $types .= $type;
    }
}

if (empty($updates)) {
    jsonResponse(false, 'No fields to update');
    return;
}

$params[] = $id;
$types .= 'i';

$sql = "UPDATE customer_requests SET " . implode(', ', $updates) . " WHERE id = ?";
$affected = dbExecute($conn, $sql, $types, ...$params);

if ($affected === -1) {
    jsonResponse(false, 'Failed to update request. Database error.', null, 500);
    return;
}

$updatedRequest = dbFetchOne($conn, "SELECT * FROM customer_requests WHERE id = ?", 'i', $id);

$userName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';
$logDetails = "Request ID: $id, Name: $name";
$oldStatus = $existingRequest['status'] ?? '';
$newStatus = $status ?: $oldStatus;
if ($oldStatus !== $newStatus) {
    $logDetails .= ", Status changed from $oldStatus to $newStatus";
}
logActivity($conn, 'Updated customer request', $logDetails, $userName);

jsonResponse(true, 'Request updated successfully', [
    'request' => $updatedRequest,
    'affected_rows' => $affected,
    'fields_updated' => array_keys($updates),
    'status_changed' => ($oldStatus !== $newStatus)
]);

// ============================================================
// CLOSE CONNECTION
// ============================================================

// The database connection is managed by db.php
?>