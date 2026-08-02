<?php
// ============================================================
// CIBIL REPAIR CRM - Save Entity API (Bank/Lawyer/CA/Franchise/etc.)
// Endpoint: /api/save_bank.php
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

requireAuth();

$userRole = $_SESSION['user_role'] ?? '';
$allowedRoles = ['admin', 'super_admin', 'manager'];
$isTestMode = isset($_GET['test']) && $_GET['test'] === 'true';

if (!$isTestMode && !in_array($userRole, $allowedRoles)) {
    jsonResponse(false, 'Unauthorized. Admin access required.', null, 403);
}

// ============================================================
// VALIDATE REQUEST METHOD
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method. Use POST.', null, 405);
}

// ============================================================
// CSRF VALIDATION
// ============================================================

if (!$isTestMode && !validateCSRF()) {
    jsonResponse(false, 'Invalid CSRF token. Please refresh and try again.', null, 403);
}

// ============================================================
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// ============================================================
// ENTITY TYPE DEFINITIONS
// ============================================================

$entityTypes = [
    'bank' => 'Bank',
    'lawyer' => 'Law Firm / Advocate',
    'ca' => 'Chartered Accountant',
    'franchise' => 'Franchise Store',
    'real_estate' => 'Real Estate Agent',
    'insurance' => 'Insurance Agent',
    'consultant' => 'Business Consultant',
    'agency' => 'Recruitment Agency',
    'broker' => 'Broker / Agent',
    'other' => 'Other'
];

// ============================================================
// ENTITY INFORMATION
// ============================================================

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$contact = isset($input['contact']) ? trim($input['contact']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$status = isset($input['status']) ? trim($input['status']) : 'active';

// Entity type
$entity_type = isset($input['entity_type']) ? trim($input['entity_type']) : 'bank';
$entity_type_label = isset($entityTypes[$entity_type]) ? $entityTypes[$entity_type] : 'Other';

// ============================================================
// EXTENDED FIELDS (Stored in notes)
// ============================================================

$notes = isset($input['notes']) ? trim($input['notes']) : '';

// Business details
$business_name = isset($input['business_name']) ? trim($input['business_name']) : '';
$owner_name = isset($input['owner_name']) ? trim($input['owner_name']) : '';
$registration_number = isset($input['registration_number']) ? trim($input['registration_number']) : '';
$gst_number = isset($input['gst_number']) ? strtoupper(trim($input['gst_number'])) : '';
$pan_number = isset($input['pan_number']) ? strtoupper(trim($input['pan_number'])) : '';
$website = isset($input['website']) ? trim($input['website']) : '';

// Address details
$address = isset($input['address']) ? trim($input['address']) : '';
$city = isset($input['city']) ? trim($input['city']) : '';
$state = isset($input['state']) ? trim($input['state']) : '';
$pincode = isset($input['pincode']) ? trim($input['pincode']) : '';
$country = isset($input['country']) ? trim($input['country']) : 'India';

// Bank details
$bank_name = isset($input['bank_name']) ? trim($input['bank_name']) : '';
$account_number = isset($input['account_number']) ? trim($input['account_number']) : '';
$ifsc_code = isset($input['ifsc_code']) ? strtoupper(trim($input['ifsc_code'])) : '';
$upi_id = isset($input['upi_id']) ? trim($input['upi_id']) : '';

// Professional details
$specialization = isset($input['specialization']) ? trim($input['specialization']) : '';
$experience_years = isset($input['experience_years']) ? intval($input['experience_years']) : 0;
$license_number = isset($input['license_number']) ? trim($input['license_number']) : '';
$affiliated_with = isset($input['affiliated_with']) ? trim($input['affiliated_with']) : '';

// ============================================================
// BUILD NOTES FROM EXTRA FIELDS
// ============================================================

$extra_fields = [];

// Entity type
$extra_fields[] = "Entity Type: $entity_type_label";

// Business details
if (!empty($business_name)) $extra_fields[] = "Business: $business_name";
if (!empty($owner_name)) $extra_fields[] = "Owner: $owner_name";
if (!empty($registration_number)) $extra_fields[] = "Registration: $registration_number";
if (!empty($gst_number)) $extra_fields[] = "GST: $gst_number";
if (!empty($pan_number)) $extra_fields[] = "PAN: $pan_number";
if (!empty($website)) $extra_fields[] = "Website: $website";

// Address
if (!empty($address)) $extra_fields[] = "Address: $address";
if (!empty($city)) $extra_fields[] = "City: $city";
if (!empty($state)) $extra_fields[] = "State: $state";
if (!empty($pincode)) $extra_fields[] = "Pincode: $pincode";
if (!empty($country)) $extra_fields[] = "Country: $country";

// Bank details
if (!empty($bank_name)) $extra_fields[] = "Bank: $bank_name";
if (!empty($account_number)) $extra_fields[] = "Account: $account_number";
if (!empty($ifsc_code)) $extra_fields[] = "IFSC: $ifsc_code";
if (!empty($upi_id)) $extra_fields[] = "UPI: $upi_id";

// Professional details
if (!empty($specialization)) $extra_fields[] = "Specialization: $specialization";
if ($experience_years > 0) $extra_fields[] = "Experience: $experience_years years";
if (!empty($license_number)) $extra_fields[] = "License: $license_number";
if (!empty($affiliated_with)) $extra_fields[] = "Affiliated: $affiliated_with";

if (!empty($extra_fields)) {
    $notes = $notes . ($notes ? "\n" : "") . implode("\n", $extra_fields);
}

// ============================================================
// VALIDATION
// ============================================================

// Required fields
if (empty($name)) {
    jsonResponse(false, 'Name is required');
    return;
}

// Validate entity type
if (!array_key_exists($entity_type, $entityTypes)) {
    jsonResponse(false, 'Invalid entity type. Allowed: ' . implode(', ', array_keys($entityTypes)));
    return;
}

// Validate email format if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format');
    return;
}

// Validate phone if provided
if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
    jsonResponse(false, 'Invalid phone number. Must be 10 digits');
    return;
}

// Validate pincode if provided
if (!empty($pincode) && !preg_match('/^[0-9]{6}$/', $pincode)) {
    jsonResponse(false, 'Invalid pincode. Must be 6 digits');
    return;
}

// Validate GST format if provided
if (!empty($gst_number) && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gst_number)) {
    jsonResponse(false, 'Invalid GST number format');
    return;
}

// Validate PAN format if provided
if (!empty($pan_number) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan_number)) {
    jsonResponse(false, 'Invalid PAN number format');
    return;
}

// Validate IFSC format if provided
if (!empty($ifsc_code) && !preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc_code)) {
    jsonResponse(false, 'Invalid IFSC code format');
    return;
}

// Validate status
$allowedStatus = ['active', 'inactive', 'suspended'];
if (!in_array($status, $allowedStatus)) {
    jsonResponse(false, 'Invalid status. Allowed: active, inactive, suspended');
    return;
}

// ============================================================
// CHECK FOR DUPLICATES (Exclude current record for update)
// ============================================================

// Check duplicate name
if ($id > 0) {
    $existingName = dbFetchOne($conn, "SELECT id FROM banks WHERE name = ? AND id != ?", 'si', $name, $id);
} else {
    $existingName = dbFetchOne($conn, "SELECT id FROM banks WHERE name = ?", 's', $name);
}
if ($existingName) {
    jsonResponse(false, 'Name already exists');
    return;
}

// Check duplicate email
if (!empty($email)) {
    if ($id > 0) {
        $existingEmail = dbFetchOne($conn, "SELECT id FROM banks WHERE email = ? AND id != ?", 'si', $email, $id);
    } else {
        $existingEmail = dbFetchOne($conn, "SELECT id FROM banks WHERE email = ?", 's', $email);
    }
    if ($existingEmail) {
        jsonResponse(false, 'Email already exists');
        return;
    }
}

// Check duplicate phone
if (!empty($phone)) {
    if ($id > 0) {
        $existingPhone = dbFetchOne($conn, "SELECT id FROM banks WHERE phone = ? AND id != ?", 'si', $phone, $id);
    } else {
        $existingPhone = dbFetchOne($conn, "SELECT id FROM banks WHERE phone = ?", 's', $phone);
    }
    if ($existingPhone) {
        jsonResponse(false, 'Phone number already exists');
        return;
    }
}

// ============================================================
// SAVE ENTITY (INSERT OR UPDATE)
// ============================================================

if ($id > 0) {
    // UPDATE existing entity
    $sql = "UPDATE banks SET 
                name=?, 
                contact=?, 
                email=?, 
                phone=?, 
                status=?, 
                notes=? 
            WHERE id=?";

    $affected = dbExecute($conn, $sql, 'ssssssi', 
        $name, $contact, $email, $phone, $status, $notes, $id
    );

    if ($affected !== -1) {
        logActivity($conn, 'Updated ' . $entity_type_label, "Entity ID: $id, Name: $name");
        $entity = dbFetchOne($conn, "SELECT * FROM banks WHERE id = ?", 'i', $id);
        jsonResponse(true, $entity_type_label . ' updated successfully', ['entity' => $entity]);
    } else {
        jsonResponse(false, 'Failed to update ' . $entity_type_label, null, 500);
    }
} else {
    // INSERT new entity
    $sql = "INSERT INTO banks (
                name, contact, email, phone, status, notes
            ) VALUES (
                ?, ?, ?, ?, ?, ?
            )";

    $affected = dbExecute($conn, $sql, 'ssssss', 
        $name, $contact, $email, $phone, $status, $notes
    );

    if ($affected !== -1) {
        $id = dbLastId($conn);
        logActivity($conn, 'Created ' . $entity_type_label, "Entity ID: $id, Name: $name");
        $entity = dbFetchOne($conn, "SELECT * FROM banks WHERE id = ?", 'i', $id);
        jsonResponse(true, $entity_type_label . ' created successfully', ['entity' => $entity]);
    } else {
        jsonResponse(false, 'Failed to create ' . $entity_type_label, null, 500);
    }
}

// ============================================================
// CLOSE CONNECTION
// ============================================================

// The database connection is managed by db.php
?>