<?php
// ============================================================
// CIBIL REPAIR CRM - Update Expense API (with GST)
// Endpoint: /api/update_expense.php
// Method: POST, PUT
// ============================================================

// Include database helpers
require_once __DIR__ . '/db.php';

// ============================================================
// GST CONFIGURATION
// ============================================================

define('GST_RATE', 18);
define('GST_CGST', 9);
define('GST_SGST', 9);

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
// EXPENSE INFORMATION
// ============================================================

$id = isset($input['id']) ? intval($input['id']) : 0;
$category = isset($input['category']) ? trim($input['category']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$date = isset($input['date']) ? trim($input['date']) : date('Y-m-d');
$payment_method = isset($input['payment_method']) ? trim($input['payment_method']) : '';
$vendor_name = isset($input['vendor_name']) ? trim($input['vendor_name']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';
$status = isset($input['status']) ? trim($input['status']) : 'pending';

// ============================================================
// VALIDATION
// ============================================================

if (!$id) {
    jsonResponse(false, 'Expense ID is required', null, 400);
    return;
}

if (empty($category)) {
    jsonResponse(false, 'Category is required');
    return;
}

if ($amount <= 0) {
    jsonResponse(false, 'Valid amount is required');
    return;
}

// Validate date if provided
if (!empty($date) && !strtotime($date)) {
    jsonResponse(false, 'Invalid date format');
    return;
}

// Validate status if provided
$allowedStatus = ['pending', 'approved', 'rejected', 'paid'];
if (!empty($status) && !in_array($status, $allowedStatus)) {
    jsonResponse(false, 'Invalid status. Allowed: pending, approved, rejected, paid');
    return;
}

// ============================================================
// CHECK IF EXPENSE EXISTS
// ============================================================

$existingExpense = dbFetchOne($conn, "SELECT * FROM expenses WHERE id = ?", 'i', $id);
if (!$existingExpense) {
    jsonResponse(false, 'Expense not found', null, 404);
    return;
}

// ============================================================
// CHECK FOR DUPLICATE EXPENSE (Exclude current)
// ============================================================

$duplicate = dbFetchOne($conn, 
    "SELECT id FROM expenses WHERE category = ? AND amount = ? AND date = ? AND id != ?", 
    'sdsi', $category, $amount, $date, $id
);
if ($duplicate) {
    jsonResponse(false, 'Duplicate expense found');
    return;
}

// ============================================================
// BUILD UPDATE QUERY
// ============================================================

$updates = [];
$params = [];
$types = '';

if (!empty($category) && $category !== $existingExpense['category']) {
    $updates[] = "category = ?";
    $params[] = $category;
    $types .= 's';
}
if (!empty($description) && $description !== $existingExpense['description']) {
    $updates[] = "description = ?";
    $params[] = $description;
    $types .= 's';
}
if ($amount > 0 && $amount != $existingExpense['amount']) {
    $updates[] = "amount = ?";
    $params[] = $amount;
    $types .= 'd';
}
if (!empty($date) && $date !== $existingExpense['date']) {
    $updates[] = "date = ?";
    $params[] = $date;
    $types .= 's';
}
if (!empty($payment_method) && $payment_method !== $existingExpense['payment_method']) {
    $updates[] = "payment_method = ?";
    $params[] = $payment_method;
    $types .= 's';
}
if (!empty($vendor_name) && $vendor_name !== $existingExpense['vendor_name']) {
    $updates[] = "vendor_name = ?";
    $params[] = $vendor_name;
    $types .= 's';
}
if (!empty($status) && $status !== $existingExpense['status']) {
    $updates[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}
if (!empty($notes) && $notes !== $existingExpense['notes']) {
    $updates[] = "notes = ?";
    $params[] = $notes;
    $types .= 's';
}

if (empty($updates)) {
    jsonResponse(false, 'No fields to update');
    return;
}

$params[] = $id;
$types .= 'i';

// ============================================================
// UPDATE EXPENSE
// ============================================================

$sql = "UPDATE expenses SET " . implode(', ', $updates) . " WHERE id = ?";
$affected = dbExecute($conn, $sql, $types, ...$params);

if ($affected === -1) {
    jsonResponse(false, 'Failed to update expense. Database error.', null, 500);
    return;
}

// ============================================================
// GET UPDATED EXPENSE
// ============================================================

$updatedExpense = dbFetchOne($conn, "SELECT * FROM expenses WHERE id = ?", 'i', $id);

// ============================================================
// LOG ACTIVITY
// ============================================================

$userName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'System';
logActivity($conn, 'Updated expense', "Expense ID: $id, Category: $category", $userName);

// ============================================================
// SUCCESS RESPONSE
// ============================================================

jsonResponse(true, 'Expense updated successfully', [
    'expense' => $updatedExpense,
    'affected_rows' => $affected
]);

// ============================================================
// CLOSE CONNECTION
// ============================================================

// The database connection is managed by db.php
?>