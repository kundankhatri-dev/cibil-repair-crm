<?php
// ============================================================
// CIBIL REPAIR CRM - Save Partner API (Add or Update)
// Endpoint: /api/save_partner.php
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
// PARTNER INFORMATION (Matches Database Schema)
// ============================================================

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$location = isset($input['location']) ? trim($input['location']) : '';
$owner = isset($input['owner']) ? trim($input['owner']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$commission_rate = isset($input['commission_rate']) ? intval($input['commission_rate']) : 10;
$status = isset($input['status']) ? trim($input['status']) : 'active';

// ============================================================
// EXTRA FIELDS (Optional - stored in notes)
// ============================================================

$notes = isset($input['notes']) ? trim($input['notes']) : '';

$extra_fields = [];
if (isset($input['company_name']) && !empty($input['company_name'])) {
    $extra_fields[] = "Company: " . trim($input['company_name']);
}
if (isset($input['contact_person']) && !empty($input['contact_person'])) {
    $extra_fields[] = "Contact Person: " . trim($input['contact_person']);
}
if (isset($input['partner_type']) && !empty($input['partner_type'])) {
    $extra_fields[] = "Type: " . trim($input['partner_type']);
}
if (isset($input['gst_number']) && !empty($input['gst_number'])) {
    $extra_fields[] = "GST: " . strtoupper(trim($input['gst_number']));
}
if (isset($input['pan_number']) && !empty($input['pan_number'])) {
    $extra_fields[] = "PAN: " . strtoupper(trim($input['pan_number']));
}
if (isset($input['website']) && !empty($input['website'])) {
    $extra_fields[] = "Website: " . trim($input['website']);
}
if (isset($input['bank_name']) && !empty($input['bank_name'])) {
    $extra_fields[] = "Bank: " . trim($input['bank_name']);
}
if (isset($input['account_number']) && !empty($input['account_number'])) {
    $extra_fields[] = "Account: " . trim($input['account_number']);
}
if (isset($input['ifsc_code']) && !empty($input['ifsc_code'])) {
    $extra_fields[] = "IFSC: " . strtoupper(trim($input['ifsc_code']));
}
if (isset($input['upi_id']) && !empty($input['upi_id'])) {
    $extra_fields[] = "UPI: " . trim($input['upi_id']);
}

if (!empty($extra_fields)) {
    $notes = $notes . ($notes ? "\n" : "") . implode("\n", $extra_fields);
}

// ============================================================
// VALIDATION
// ============================================================

// Required fields
if (empty($name)) {
    jsonResponse(false, 'Partner name is required');
    return;
}

if (empty($phone)) {
    jsonResponse(false, 'Phone number is required');
    return;
}

// Email validation
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format');
    return;
}

// Phone validation
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    jsonResponse(false, 'Invalid phone number. Must be 10 digits');
    return;
}

// Status validation
$allowedStatus = ['active', 'inactive', 'pending'];
if (!in_array($status, $allowedStatus)) {
    jsonResponse(false, 'Invalid status. Allowed: active, inactive, pending');
    return;
}

// Commission validation
if ($commission_rate < 0 || $commission_rate > 100) {
    jsonResponse(false, 'Commission rate must be between 0 and 100');
    return;
}

// ============================================================
// CHECK FOR DUPLICATES (Exclude current record for update)
// ============================================================

// Check duplicate email
if (!empty($email)) {
    if ($id > 0) {
        $existingEmail = dbFetchOne($conn, "SELECT id FROM partners WHERE email = ? AND id != ?", 'si', $email, $id);
    } else {
        $existingEmail = dbFetchOne($conn, "SELECT id FROM partners WHERE email = ?", 's', $email);
    }
    if ($existingEmail) {
        jsonResponse(false, 'Email already exists for another partner');
        return;
    }
}

// Check duplicate phone
if ($id > 0) {
    $existingPhone = dbFetchOne($conn, "SELECT id FROM partners WHERE phone = ? AND id != ?", 'si', $phone, $id);
} else {
    $existingPhone = dbFetchOne($conn, "SELECT id FROM partners WHERE phone = ?", 's', $phone);
}
if ($existingPhone) {
    jsonResponse(false, 'Phone number already exists for another partner');
    return;
}

// Check duplicate name
if ($id > 0) {
    $existingName = dbFetchOne($conn, "SELECT id FROM partners WHERE name = ? AND id != ?", 'si', $name, $id);
} else {
    $existingName = dbFetchOne($conn, "SELECT id FROM partners WHERE name = ?", 's', $name);
}
if ($existingName) {
    jsonResponse(false, 'Partner name already exists');
    return;
}

// ============================================================
// SAVE PARTNER (INSERT OR UPDATE)
// ============================================================

if ($id > 0) {
    // UPDATE existing partner
    $sql = "UPDATE partners SET 
                name=?, 
                location=?, 
                owner=?, 
                phone=?, 
                email=?, 
                commission_rate=?, 
                status=? 
            WHERE id=?";

    $affected = dbExecute($conn, $sql, 'sssssdsi', 
        $name, $location, $owner, $phone, $email, $commission_rate, $status, $id
    );

    if ($affected !== -1) {
        logActivity($conn, 'Updated partner', "Partner ID: $id, Name: $name");
        $partner = dbFetchOne($conn, "SELECT * FROM partners WHERE id = ?", 'i', $id);
        jsonResponse(true, 'Partner updated successfully', ['partner' => $partner]);
    } else {
        jsonResponse(false, 'Failed to update partner', null, 500);
    }
} else {
    // INSERT new partner
    $sql = "INSERT INTO partners (
                name, location, owner, phone, email, commission_rate, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?
            )";

    $affected = dbExecute($conn, $sql, 'sssssds', 
        $name, $location, $owner, $phone, $email, $commission_rate, $status
    );

    if ($affected !== -1) {
        $id = dbLastId($conn);
        logActivity($conn, 'Created partner', "Partner ID: $id, Name: $name");
        $partner = dbFetchOne($conn, "SELECT * FROM partners WHERE id = ?", 'i', $id);
        jsonResponse(true, 'Partner created successfully', ['partner' => $partner]);
    } else {
        jsonResponse(false, 'Failed to create partner', null, 500);
    }
}

// ============================================================
// CLOSE CONNECTION
// ============================================================

// The database connection is managed by db.php
?>