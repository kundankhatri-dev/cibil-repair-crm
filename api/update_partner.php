<?php
// ============================================================
// CIBIL REPAIR CRM - Update Partner API
// Endpoint: /api/update_partner.php
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
// PARTNER INFORMATION
// ============================================================

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$location = isset($input['location']) ? trim($input['location']) : '';
$owner = isset($input['owner']) ? trim($input['owner']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$commission_rate = isset($input['commission_rate']) ? intval($input['commission_rate']) : 10;
$status = isset($input['status']) ? trim($input['status']) : 'active';
$notes = isset($input['notes']) ? trim($input['notes']) : '';

// ============================================================
// VALIDATION
// ============================================================

if (!$id) {
    jsonResponse(false, 'Partner ID is required', null, 400);
    return;
}

if (empty($name)) {
    jsonResponse(false, 'Partner name is required');
    return;
}

if (empty($phone)) {
    jsonResponse(false, 'Phone number is required');
    return;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format');
    return;
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    jsonResponse(false, 'Invalid phone number. Must be 10 digits');
    return;
}

$allowedStatus = ['active', 'inactive', 'pending'];
if (!in_array($status, $allowedStatus)) {
    jsonResponse(false, 'Invalid status. Allowed: active, inactive, pending');
    return;
}

if ($commission_rate < 0 || $commission_rate > 100) {
    jsonResponse(false, 'Commission rate must be between 0 and 100');
    return;
}

// ============================================================
// CHECK IF PARTNER EXISTS
// ============================================================

$partner = dbFetchOne($conn, "SELECT * FROM partners WHERE id = ?", 'i', $id);
if (!$partner) {
    jsonResponse(false, 'Partner not found', null, 404);
    return;
}

// ============================================================
// CHECK FOR DUPLICATES
// ============================================================

if (!empty($email)) {
    $existingEmail = dbFetchOne($conn, "SELECT id FROM partners WHERE email = ? AND id != ?", 'si', $email, $id);
    if ($existingEmail) {
        jsonResponse(false, 'Email already exists for another partner');
        return;
    }
}

$existingPhone = dbFetchOne($conn, "SELECT id FROM partners WHERE phone = ? AND id != ?", 'si', $phone, $id);
if ($existingPhone) {
    jsonResponse(false, 'Phone number already exists for another partner');
    return;
}

$existingName = dbFetchOne($conn, "SELECT id FROM partners WHERE name = ? AND id != ?", 'si', $name, $id);
if ($existingName) {
    jsonResponse(false, 'Partner name already exists');
    return;
}

// ============================================================
// UPDATE PARTNER
// ============================================================

$sql = "UPDATE partners SET 
            name = ?, 
            location = ?, 
            owner = ?, 
            phone = ?, 
            email = ?, 
            commission_rate = ?, 
            status = ? 
        WHERE id = ?";

$affected = dbExecute($conn, $sql, 'sssssdsi', 
    $name, $location, $owner, $phone, $email, $commission_rate, $status, $id
);

if ($affected === -1) {
    jsonResponse(false, 'Failed to update partner. Database error.', null, 500);
    return;
}

// ============================================================
// GET UPDATED PARTNER
// ============================================================

$updatedPartner = dbFetchOne($conn, "SELECT * FROM partners WHERE id = ?", 'i', $id);

// ============================================================
// LOG ACTIVITY
// ============================================================

$userName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';
logActivity($conn, 'Updated partner', "Partner ID: $id, Name: $name", $userName);

// ============================================================
// SUCCESS RESPONSE
// ============================================================

jsonResponse(true, 'Partner updated successfully', [
    'partner' => $updatedPartner,
    'affected_rows' => $affected
]);

// ============================================================
// CLOSE CONNECTION
// ============================================================

// The database connection is managed by db.php
?>