<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$dbHost = 'localhost';
$dbUser = 'u929623538_cibilrepair';
$dbPass = 'Kundanlaxmi@1995';
$dbName = 'u929623538_cibil';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$id = isset($input['id']) ? intval($input['id']) : 0;
$customer_name = isset($input['customer_name']) ? trim($input['customer_name']) : '';
$customer_email = isset($input['customer_email']) ? trim($input['customer_email']) : '';
$customer_phone = isset($input['customer_phone']) ? trim($input['customer_phone']) : '';
$customer_gst = isset($input['customer_gst']) ? strtoupper(trim($input['customer_gst'])) : '';
$customer_pan = isset($input['customer_pan']) ? strtoupper(trim($input['customer_pan'])) : '';
$customer_address = isset($input['customer_address']) ? trim($input['customer_address']) : '';
$customer_city = isset($input['customer_city']) ? trim($input['customer_city']) : '';
$customer_state = isset($input['customer_state']) ? trim($input['customer_state']) : '';
$customer_pincode = isset($input['customer_pincode']) ? trim($input['customer_pincode']) : '';
$service = isset($input['service']) ? trim($input['service']) : '';
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$gst_rate = isset($input['gst_rate']) ? floatval($input['gst_rate']) : 18;
$status = isset($input['status']) ? trim($input['status']) : '';
$valid_until = isset($input['valid_until']) ? trim($input['valid_until']) : '';
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Quotation ID is required']);
    exit;
}

// Check if quotation exists
$checkStmt = $pdo->prepare("SELECT id, quote_no FROM quotations WHERE id = ?");
$checkStmt->execute([$id]);
$existing = $checkStmt->fetch();

if (!$existing) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Quotation not found']);
    exit;
}

// Build update query dynamically
$updateFields = [];
$params = [];

if (!empty($customer_name)) {
    $updateFields[] = "customer_name = ?";
    $params[] = $customer_name;
}
if (!empty($customer_email)) {
    $updateFields[] = "customer_email = ?";
    $params[] = $customer_email;
}
if (!empty($customer_phone)) {
    $updateFields[] = "customer_phone = ?";
    $params[] = $customer_phone;
}
if (!empty($customer_gst)) {
    $updateFields[] = "customer_gst = ?";
    $params[] = $customer_gst;
}
if (!empty($customer_pan)) {
    $updateFields[] = "customer_pan = ?";
    $params[] = $customer_pan;
}
if (!empty($customer_address)) {
    $updateFields[] = "customer_address = ?";
    $params[] = $customer_address;
}
if (!empty($customer_city)) {
    $updateFields[] = "customer_city = ?";
    $params[] = $customer_city;
}
if (!empty($customer_state)) {
    $updateFields[] = "customer_state = ?";
    $params[] = $customer_state;
}
if (!empty($customer_pincode)) {
    $updateFields[] = "customer_pincode = ?";
    $params[] = $customer_pincode;
}
if (!empty($service)) {
    $updateFields[] = "service = ?";
    $params[] = $service;
}
if ($amount > 0) {
    $updateFields[] = "amount = ?";
    $params[] = $amount;
    
    // Recalculate GST
    $gst_amount = round($amount * ($gst_rate / 100), 2);
    $cgst_amount = round($gst_amount / 2, 2);
    $sgst_amount = round($gst_amount / 2, 2);
    $total_with_gst = round($amount + $gst_amount, 2);
    
    $updateFields[] = "gst_amount = ?";
    $params[] = $gst_amount;
    $updateFields[] = "cgst_amount = ?";
    $params[] = $cgst_amount;
    $updateFields[] = "sgst_amount = ?";
    $params[] = $sgst_amount;
    $updateFields[] = "total_with_gst = ?";
    $params[] = $total_with_gst;
}
if (!empty($status)) {
    $updateFields[] = "status = ?";
    $params[] = $status;
}
if (!empty($valid_until)) {
    $updateFields[] = "valid_until = ?";
    $params[] = $valid_until;
}
if (!empty($notes)) {
    $updateFields[] = "notes = ?";
    $params[] = $notes;
}

if (empty($updateFields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit;
}

// Add updated_at
$updateFields[] = "updated_at = NOW()";

// Add id to params
$params[] = $id;

$sql = "UPDATE quotations SET " . implode(', ', $updateFields) . " WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Get updated quotation
$selectStmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
$selectStmt->execute([$id]);
$updated = $selectStmt->fetch();

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Quotation updated successfully',
    'quotation' => $updated
]);

exit;
?>