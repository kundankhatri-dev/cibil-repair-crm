<?php
// ============================================================
// CIBIL REPAIR CRM - Add Sale API (FIXED - Matches Table)
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADER =====
header('Content-Type: application/json');

// ============================================================
// DATABASE CONNECTION
// ============================================================

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// ============================================================
// GET INPUT DATA
// ============================================================

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!$input) {
    $input = $_POST;
}

// ============================================================
// VALIDATE
// ============================================================

$customer_name = isset($input['customer_name']) ? trim($input['customer_name']) : '';
if (empty($customer_name)) {
    $customer_name = isset($input['customer']) ? trim($input['customer']) : '';
}

if (empty($customer_name)) {
    echo json_encode(['success' => false, 'error' => 'Customer name is required']);
    exit;
}

$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid amount is required']);
    exit;
}

// ============================================================
// ESCAPE VALUES - MATCHING YOUR TABLE COLUMNS
// ============================================================

$lead_id = isset($input['lead_id']) ? intval($input['lead_id']) : 0;
$customer_name = mysqli_real_escape_string($conn, $customer_name);
$customer_email = mysqli_real_escape_string($conn, $input['customer_email'] ?? '');
$customer_phone = mysqli_real_escape_string($conn, $input['customer_phone'] ?? '');
$customer_gst = mysqli_real_escape_string($conn, strtoupper($input['customer_gst'] ?? ''));
$customer_pan = mysqli_real_escape_string($conn, strtoupper($input['customer_pan'] ?? ''));
$customer_address = mysqli_real_escape_string($conn, $input['customer_address'] ?? '');
$customer_city = mysqli_real_escape_string($conn, $input['customer_city'] ?? '');
$customer_state = mysqli_real_escape_string($conn, $input['customer_state'] ?? '');
$customer_pincode = mysqli_real_escape_string($conn, $input['customer_pincode'] ?? '');
$service = mysqli_real_escape_string($conn, $input['service'] ?? 'Written Off');
$amount = floatval($input['amount']);
$gst_rate = isset($input['gst_rate']) ? floatval($input['gst_rate']) : 18;
$gst_amount = $amount * ($gst_rate / 100);
$cgst_amount = $amount * ($gst_rate / 2 / 100);
$sgst_amount = $amount * ($gst_rate / 2 / 100);
$total_with_gst = $amount + $gst_amount;
$is_gst_applicable = isset($input['is_gst_applicable']) ? intval($input['is_gst_applicable']) : 1;
$payment_method = mysqli_real_escape_string($conn, $input['payment_method'] ?? 'UPI');
$invoice_no = mysqli_real_escape_string($conn, $input['invoice_no'] ?? '');
$commission_amount = isset($input['commission_amount']) ? floatval($input['commission_amount']) : 0;
$partner_id = isset($input['partner_id']) ? intval($input['partner_id']) : 0;
$status = mysqli_real_escape_string($conn, $input['status'] ?? 'Completed');
$sale_date = isset($input['date']) ? mysqli_real_escape_string($conn, $input['date']) : date('Y-m-d');
$notes = mysqli_real_escape_string($conn, $input['notes'] ?? '');

// ============================================================
// INSERT SALE - MATCHING YOUR TABLE COLUMNS
// ============================================================

$sql = "INSERT INTO sales (
    lead_id, customer_name, customer_email, customer_phone, 
    customer_gst, customer_pan, customer_address, customer_city, 
    customer_state, customer_pincode, service, amount, 
    gst_rate, gst_amount, cgst_amount, sgst_amount, 
    total_with_gst, is_gst_applicable, payment_method, invoice_no,
    commission_amount, partner_id, status, sale_date
) VALUES (
    $lead_id, '$customer_name', '$customer_email', '$customer_phone',
    '$customer_gst', '$customer_pan', '$customer_address', '$customer_city',
    '$customer_state', '$customer_pincode', '$service', $amount,
    $gst_rate, $gst_amount, $cgst_amount, $sgst_amount,
    $total_with_gst, $is_gst_applicable, '$payment_method', '$invoice_no',
    $commission_amount, $partner_id, '$status', '$sale_date'
)";

$result = mysqli_query($conn, $sql);

if ($result) {
    $id = mysqli_insert_id($conn);
    
    // Get the inserted sale
    $result2 = mysqli_query($conn, "SELECT * FROM sales WHERE id = $id");
    $sale = mysqli_fetch_assoc($result2);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sale added successfully',
        'data' => [
            'sale' => $sale,
            'gst_details' => [
                'base_amount' => $amount,
                'gst_rate' => $gst_rate,
                'gst_amount' => $gst_amount,
                'cgst_amount' => $cgst_amount,
                'sgst_amount' => $sgst_amount,
                'total_with_gst' => $total_with_gst
            ]
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
exit;
?>