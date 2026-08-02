<?php
// ============================================================
// CIBIL REPAIR CRM - Add Lead API
// Endpoint: /api/add_lead.php
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
header('Access-Control-Allow-Headers: Content-Type');

// ===== REMOVED: Direct access check =====
// if (basename($_SERVER['PHP_SELF']) === 'add_lead.php') {
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
// GET INPUT DATA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// ============================================================
// LEAD INFORMATION
// ============================================================

$name = isset($input['name']) ? trim($input['name']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$source = isset($input['source']) ? trim($input['source']) : 'website';
$status = isset($input['status']) ? trim($input['status']) : 'new';
$priority = isset($input['priority']) ? trim($input['priority']) : 'medium';
$service = isset($input['service']) ? trim($input['service']) : 'CIBIL Repair';
$partner_id = isset($input['partner_id']) ? intval($input['partner_id']) : 0;
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : '';

// ============================================================
// VALIDATION
// ============================================================

if (empty($name)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Name is required']);
    exit();
}

if (empty($phone)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Phone number is required']);
    exit();
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid phone number. Must be 10 digits']);
    exit();
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit();
}

$allowedStatus = ['new', 'contacted', 'converted', 'lost'];
if (!in_array($status, $allowedStatus)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid status. Allowed: new, contacted, converted, lost']);
    exit();
}

$allowedPriority = ['low', 'medium', 'high', 'urgent'];
if (!in_array($priority, $allowedPriority)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid priority. Allowed: low, medium, high, urgent']);
    exit();
}

$allowedSources = ['website', 'referral', 'google_ads', 'facebook', 'instagram', 'linkedin', 'email', 'call', 'walk_in', 'other'];
if (!in_array($source, $allowedSources)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid source']);
    exit();
}

// ============================================================
// CHECK FOR DUPLICATES
// ============================================================

$existingLead = dbFetchOne($conn, 
    "SELECT id FROM leads WHERE phone = ? AND status NOT IN ('converted', 'lost')", 
    's', $phone
);
if ($existingLead) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Lead with this phone number already exists']);
    exit();
}

if (!empty($email)) {
    $existingEmail = dbFetchOne($conn, 
        "SELECT id FROM leads WHERE email = ? AND status NOT IN ('converted', 'lost')", 
        's', $email
    );
    if ($existingEmail) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Lead with this email already exists']);
        exit();
    }
}

// ============================================================
// INSERT LEAD
// ============================================================

$sql = "INSERT INTO leads (
    name, phone, email, message, source, status, priority, 
    service, partner_id, amount, notes
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)";

$affected = dbExecute($conn, $sql, 'ssssssssids', 
    $name, $phone, $email, $message, $source, $status, $priority,
    $service, $partner_id, $amount, $notes
);

if ($affected === -1) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Failed to create lead. Database error.']);
    exit();
}

// ============================================================
// GET THE NEW LEAD
// ============================================================

$id = dbLastId($conn);
$lead = dbFetchOne($conn, "SELECT * FROM leads WHERE id = ?", 'i', $id);

// ============================================================
// SUCCESS RESPONSE
// ============================================================

ob_clean();
echo json_encode([
    'success' => true,
    'message' => 'Lead added successfully',
    'data' => $lead
]);

exit();
?>