<?php
// ============================================================
// CIBIL REPAIR CRM - Save Lead API (Add or Update)
// Endpoint: /api/save_lead.php
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

$isTestMode = isset($_GET['test']) && $_GET['test'] === 'true';
// if (!$isTestMode) {
//     $userRole = $_SESSION['user_role'] ?? '';
//     $allowedRoles = ['admin', 'super_admin', 'manager'];
//     if (!in_array($userRole, $allowedRoles)) {
//         jsonResponse(false, 'Unauthorized. Admin access required.', null, 403);
//     }
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
// LEAD INFORMATION
// ============================================================

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$source = isset($input['source']) ? trim($input['source']) : 'website';
$status = isset($input['status']) ? trim($input['status']) : 'new';
$priority = isset($input['priority']) ? trim($input['priority']) : 'medium';
$service = isset($input['service']) ? trim($input['service']) : 'CIBIL Repair';
$service_name = isset($input['service_name']) ? trim($input['service_name']) : $service;
$partner_id = isset($input['partner_id']) ? intval($input['partner_id']) : 0;
$source_type = isset($input['source_type']) ? trim($input['source_type']) : 'direct';
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$aadhar = isset($input['aadhar']) ? trim($input['aadhar']) : '';
$pan = isset($input['pan']) ? strtoupper(trim($input['pan'])) : '';
$score = isset($input['score']) ? intval($input['score']) : 0;
$assigned_to = isset($input['assigned_to']) ? intval($input['assigned_to']) : 0;

// ============================================================
// VALIDATION
// ============================================================

// Required fields
if (empty($name)) {
    jsonResponse(false, 'Name is required');
    return;
}

if (empty($phone)) {
    jsonResponse(false, 'Phone number is required');
    return;
}

// Validate phone format
if (!preg_match('/^[0-9]{10}$/', $phone)) {
    jsonResponse(false, 'Invalid phone number. Must be 10 digits');
    return;
}

// Validate email format if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format');
    return;
}

// Validate status
$allowedStatus = ['new', 'contacted', 'converted', 'lost'];
if (!in_array($status, $allowedStatus)) {
    jsonResponse(false, 'Invalid status. Allowed: new, contacted, converted, lost');
    return;
}

// Validate priority
$allowedPriority = ['low', 'medium', 'high', 'urgent'];
if (!in_array($priority, $allowedPriority)) {
    jsonResponse(false, 'Invalid priority. Allowed: low, medium, high, urgent');
    return;
}

// Validate source
$allowedSources = ['website', 'referral', 'google_ads', 'facebook', 'instagram', 'linkedin', 'email', 'call', 'walk_in', 'other'];
if (!in_array($source, $allowedSources)) {
    jsonResponse(false, 'Invalid source');
    return;
}

// Validate source type
$allowedSourceTypes = ['direct', 'referral', 'connector'];
if (!in_array($source_type, $allowedSourceTypes)) {
    jsonResponse(false, 'Invalid source type. Allowed: direct, referral, connector');
    return;
}

// Validate Aadhar if provided
if (!empty($aadhar) && !preg_match('/^[0-9]{12}$/', $aadhar)) {
    jsonResponse(false, 'Invalid Aadhar number. Must be 12 digits');
    return;
}

// Validate PAN if provided
if (!empty($pan) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
    jsonResponse(false, 'Invalid PAN number format');
    return;
}

// ============================================================
// CHECK FOR DUPLICATES (Exclude current record for update)
// ============================================================

// Check duplicate phone
if ($id > 0) {
    $existingPhone = dbFetchOne($conn, 
        "SELECT id FROM leads WHERE phone = ? AND id != ? AND status NOT IN ('converted', 'lost')", 
        'si', $phone, $id
    );
} else {
    $existingPhone = dbFetchOne($conn, 
        "SELECT id FROM leads WHERE phone = ? AND status NOT IN ('converted', 'lost')", 
        's', $phone
    );
}
if ($existingPhone) {
    jsonResponse(false, 'Lead with this phone number already exists');
    return;
}

// Check duplicate email if provided
if (!empty($email)) {
    if ($id > 0) {
        $existingEmail = dbFetchOne($conn, 
            "SELECT id FROM leads WHERE email = ? AND id != ? AND status NOT IN ('converted', 'lost')", 
            'si', $email, $id
        );
    } else {
        $existingEmail = dbFetchOne($conn, 
            "SELECT id FROM leads WHERE email = ? AND status NOT IN ('converted', 'lost')", 
            's', $email
        );
    }
    if ($existingEmail) {
        jsonResponse(false, 'Lead with this email already exists');
        return;
    }
}

// ============================================================
// SAVE LEAD (INSERT OR UPDATE)
// ============================================================

if ($id > 0) {
    // UPDATE existing lead
    $sql = "UPDATE leads SET 
                name = ?,
                phone = ?,
                email = ?,
                message = ?,
                source = ?,
                status = ?,
                priority = ?,
                service = ?,
                service_name = ?,
                partner_id = ?,
                source_type = ?,
                amount = ?,
                notes = ?,
                aadhar = ?,
                pan = ?,
                score = ?,
                assigned_to = ?
            WHERE id = ?";

    $affected = dbExecute($conn, $sql, 'sssssssssidsisssi', 
        $name, $phone, $email, $message, $source, $status, $priority,
        $service, $service_name, $partner_id, $source_type, $amount, $notes,
        $aadhar, $pan, $score, $assigned_to, $id
    );

    if ($affected !== -1) {
        logActivity($conn, 'Updated lead', "Lead ID: $id, Name: $name");
        $lead = dbFetchOne($conn, "SELECT * FROM leads WHERE id = ?", 'i', $id);
        jsonResponse(true, 'Lead updated successfully', ['lead' => $lead]);
    } else {
        jsonResponse(false, 'Failed to update lead', null, 500);
    }
} else {
    // INSERT new lead
    $sql = "INSERT INTO leads (
                name, phone, email, message, source, status, priority,
                service, service_name, partner_id, source_type, amount, notes,
                aadhar, pan, score, assigned_to
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?
            )";

    $affected = dbExecute($conn, $sql, 'sssssssssidsisssi', 
        $name, $phone, $email, $message, $source, $status, $priority,
        $service, $service_name, $partner_id, $source_type, $amount, $notes,
        $aadhar, $pan, $score, $assigned_to
    );

    if ($affected !== -1) {
        $id = dbLastId($conn);
        logActivity($conn, 'Created lead', "Lead ID: $id, Name: $name, Priority: $priority");
        $lead = dbFetchOne($conn, "SELECT * FROM leads WHERE id = ?", 'i', $id);
        jsonResponse(true, 'Lead created successfully', ['lead' => $lead]);
    } else {
        jsonResponse(false, 'Failed to create lead', null, 500);
    }
}

// ============================================================
// CLOSE CONNECTION
// ============================================================

// The database connection is managed by db.php
?>