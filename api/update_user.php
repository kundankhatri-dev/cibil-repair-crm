<?php
// ============================================================
// CIBIL REPAIR CRM - Update User API
// Endpoint: /api/update_user.php
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
// $allowedRoles = ['admin', 'super_admin'];
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
// USER INFORMATION
// ============================================================

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$role = isset($input['role']) ? trim($input['role']) : '';
$status = isset($input['status']) ? trim($input['status']) : '';
$city = isset($input['city']) ? trim($input['city']) : '';
$country = isset($input['country']) ? trim($input['country']) : '';
$timezone = isset($input['timezone']) ? trim($input['timezone']) : '';
$language = isset($input['language']) ? trim($input['language']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';
$employee_code = isset($input['employee_code']) ? trim($input['employee_code']) : '';
$partner_code = isset($input['partner_code']) ? trim($input['partner_code']) : '';
$client_code = isset($input['client_code']) ? trim($input['client_code']) : '';
$joined_date = isset($input['joined_date']) ? trim($input['joined_date']) : '';
$exit_date = isset($input['exit_date']) ? trim($input['exit_date']) : '';
$employee_status = isset($input['employee_status']) ? trim($input['employee_status']) : '';
$twofa_enabled = isset($input['twofa_enabled']) ? filter_var($input['twofa_enabled'], FILTER_VALIDATE_BOOLEAN) : null;
$account_source = isset($input['account_source']) ? trim($input['account_source']) : '';
$registration_code = isset($input['registration_code']) ? trim($input['registration_code']) : '';

// ============================================================
# VALIDATION
// ============================================================

if (!$id) {
    jsonResponse(false, 'User ID is required', null, 400);
    return;
}

// Check if user exists
$existingUser = dbFetchOne($conn, "SELECT * FROM users WHERE id = ?", 'i', $id);
if (!$existingUser) {
    jsonResponse(false, 'User not found', null, 404);
    return;
}

// Validate email if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format');
    return;
}

// Check for duplicate email (exclude current user)
if (!empty($email) && $email !== $existingUser['email']) {
    $duplicate = dbFetchOne($conn, "SELECT id FROM users WHERE email = ? AND id != ?", 'si', $email, $id);
    if ($duplicate) {
        jsonResponse(false, 'Email already exists for another user');
        return;
    }
}

// Check for duplicate phone (exclude current user)
if (!empty($phone) && $phone !== $existingUser['phone']) {
    $duplicate = dbFetchOne($conn, "SELECT id FROM users WHERE phone = ? AND id != ?", 'si', $phone, $id);
    if ($duplicate) {
        jsonResponse(false, 'Phone number already exists for another user');
        return;
    }
}

// Validate role if provided
$allowedRoles = ['admin', 'super_admin', 'partner', 'client', 'employee'];
if (!empty($role) && !in_array($role, $allowedRoles)) {
    jsonResponse(false, 'Invalid role. Allowed: admin, super_admin, partner, client, employee');
    return;
}

// Validate status if provided
$allowedStatus = ['active', 'inactive', 'suspended', 'pending', 'approved'];
if (!empty($status) && !in_array($status, $allowedStatus)) {
    jsonResponse(false, 'Invalid status. Allowed: active, inactive, suspended, pending, approved');
    return;
}

// Validate employee status if provided
$allowedEmployeeStatus = ['active', 'probation', 'notice', 'inactive', 'terminated'];
if (!empty($employee_status) && !in_array($employee_status, $allowedEmployeeStatus)) {
    jsonResponse(false, 'Invalid employee status');
    return;
}

// Validate joined date if provided
if (!empty($joined_date) && !strtotime($joined_date)) {
    jsonResponse(false, 'Invalid joined date format');
    return;
}

// Validate exit date if provided
if (!empty($exit_date) && !strtotime($exit_date)) {
    jsonResponse(false, 'Invalid exit date format');
    return;
}

// Validate timezone if provided
$allowedTimezones = ['Asia/Kolkata', 'UTC', 'America/New_York', 'Europe/London', 'Asia/Dubai', 'Asia/Singapore', 'Australia/Sydney'];
if (!empty($timezone) && !in_array($timezone, $allowedTimezones)) {
    jsonResponse(false, 'Invalid timezone');
    return;
}

// Validate language if provided
$allowedLanguages = ['en', 'hi', 'ta', 'te', 'ml', 'kn', 'pa', 'gu'];
if (!empty($language) && !in_array($language, $allowedLanguages)) {
    jsonResponse(false, 'Invalid language');
    return;
}

// Validate password if provided
if (!empty($password) && strlen($password) < 6) {
    jsonResponse(false, 'Password must be at least 6 characters');
    return;
}

// ============================================================
# BUILD UPDATE FIELDS
// ============================================================

$updates = [];
$params = [];
$types = '';

// Map fields to database columns
$fieldMap = [
    'name' => 's',
    'email' => 's',
    'phone' => 's',
    'role' => 's',
    'status' => 's',
    'city' => 's',
    'country' => 's',
    'timezone' => 's',
    'language' => 's',
    'employee_code' => 's',
    'partner_code' => 's',
    'client_code' => 's',
    'joined_date' => 's',
    'exit_date' => 's',
    'employee_status' => 's',
    'account_source' => 's',
    'registration_code' => 's'
];

foreach ($fieldMap as $field => $type) {
    if (isset($input[$field]) && $input[$field] !== '') {
        $value = trim($input[$field]);
        if ($field === 'password') {
            continue;
        }
        $updates[] = "$field = ?";
        $params[] = $value;
        $types .= $type;
    }
}

// Handle password separately
if (!empty($password)) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $updates[] = "password = ?";
    $params[] = $hashedPassword;
    $types .= 's';
}

// Handle twofa_enabled (boolean)
if ($twofa_enabled !== null) {
    $updates[] = "twofa_enabled = ?";
    $params[] = $twofa_enabled ? 1 : 0;
    $types .= 'i';
}

// Handle email verification
if (isset($input['email_verified']) && $input['email_verified'] !== '') {
    $emailVerified = filter_var($input['email_verified'], FILTER_VALIDATE_BOOLEAN);
    if ($emailVerified && empty($existingUser['email_verified_at'])) {
        $updates[] = "email_verified_at = NOW()";
    } elseif (!$emailVerified && !empty($existingUser['email_verified_at'])) {
        $updates[] = "email_verified_at = NULL";
    }
}

// Handle unlock user (if locked)
if (isset($input['unlock']) && $input['unlock'] === true) {
    $updates[] = "locked_until = NULL";
    $updates[] = "login_attempts = 0";
}

if (empty($updates)) {
    jsonResponse(false, 'No fields to update');
    return;
}

// Add id to params
$params[] = $id;
$types .= 'i';

// ============================================================
# UPDATE USER
// ============================================================

$sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
$affected = dbExecute($conn, $sql, $types, ...$params);

if ($affected === -1) {
    jsonResponse(false, 'Failed to update user. Database error.', null, 500);
    return;
}

// ============================================================
# GET UPDATED USER
// ============================================================

$updatedUser = dbFetchOne($conn, "SELECT * FROM users WHERE id = ?", 'i', $id);

// ============================================================
# LOG ACTIVITY
// ============================================================

$userName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';
$logDetails = "User ID: $id, Name: " . ($name ?: $existingUser['name']);
if (!empty($role) && $role !== $existingUser['role']) {
    $logDetails .= ", Role changed from {$existingUser['role']} to $role";
}
if (!empty($status) && $status !== $existingUser['status']) {
    $logDetails .= ", Status changed from {$existingUser['status']} to $status";
}
if (!empty($password)) {
    $logDetails .= ", Password changed";
}
logActivity($conn, 'Updated user', $logDetails, $userName);

// ============================================================
# SUCCESS RESPONSE
// ============================================================

jsonResponse(true, 'User updated successfully', [
    'user' => $updatedUser,
    'affected_rows' => $affected,
    'fields_updated' => array_keys($updates)
]);

// ============================================================
# CLOSE CONNECTION
// ============================================================

// The database connection is managed by db.php
?>