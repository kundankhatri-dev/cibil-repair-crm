<?php
// ============================================================
// CIBIL REPAIR CRM - Add Quotation API (NEW)
// ============================================================

// ===== SHOW ERRORS FOR DEBUGGING =====
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

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

$customer = isset($input['customer']) ? trim($input['customer']) : '';
$customer_email = isset($input['customer_email']) ? trim($input['customer_email']) : '';
$customer_phone = isset($input['customer_phone']) ? trim($input['customer_phone']) : '';
$service = isset($input['service']) ? trim($input['service']) : 'Written Off';
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$gst_rate = isset($input['gst_rate']) ? floatval($input['gst_rate']) : 18;
$validity = isset($input['validity']) ? trim($input['validity']) : date('Y-m-d', strtotime('+30 days'));
$status = isset($input['status']) ? trim($input['status']) : 'Draft';
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if (empty($customer)) {
    echo json_encode(['success' => false, 'error' => 'Customer name is required']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid amount is required']);
    exit;
}

// ============================================================
# CREATE QUOTATIONS TABLE
// ============================================================

$createTable = "CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_no VARCHAR(50) NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255),
    customer_phone VARCHAR(20),
    service VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    gst_rate DECIMAL(5,2) DEFAULT 18.00,
    gst_amount DECIMAL(10,2) DEFAULT 0,
    total_with_gst DECIMAL(10,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Draft',
    valid_until DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $createTable);

// ============================================================
# GENERATE QUOTE NUMBER
// ============================================================

$countResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM quotations");
$row = mysqli_fetch_assoc($countResult);
$count = $row ? $row['count'] + 1 : 1;
$quote_no = 'QUO' . date('Y') . str_pad($count, 4, '0', STR_PAD_LEFT);

// ============================================================
# GST CALCULATION
// ============================================================

$gst_amount = $amount * ($gst_rate / 100);
$total_with_gst = $amount + $gst_amount;

// ============================================================
# INSERT QUOTATION
// ============================================================

$customer_escaped = mysqli_real_escape_string($conn, $customer);
$customer_email_escaped = mysqli_real_escape_string($conn, $customer_email);
$customer_phone_escaped = mysqli_real_escape_string($conn, $customer_phone);
$service_escaped = mysqli_real_escape_string($conn, $service);
$status_escaped = mysqli_real_escape_string($conn, $status);
$validity_escaped = mysqli_real_escape_string($conn, $validity);
$notes_escaped = mysqli_real_escape_string($conn, $notes);

$sql = "INSERT INTO quotations (
    quote_no, customer_name, customer_email, customer_phone, service, 
    amount, gst_rate, gst_amount, total_with_gst, status, valid_until, notes
) VALUES (
    '$quote_no', '$customer_escaped', '$customer_email_escaped', 
    '$customer_phone_escaped', '$service_escaped', 
    $amount, $gst_rate, $gst_amount, $total_with_gst, 
    '$status_escaped', '$validity_escaped', '$notes_escaped'
)";

$result = mysqli_query($conn, $sql);

if ($result) {
    $id = mysqli_insert_id($conn);
    $result2 = mysqli_query($conn, "SELECT * FROM quotations WHERE id = $id");
    $quotation = mysqli_fetch_assoc($result2);
    
    echo json_encode([
        'success' => true,
        'message' => 'Quotation added successfully',
        'data' => [
            'quotation' => $quotation,
            'gst_details' => [
                'base_amount' => $amount,
                'gst_rate' => $gst_rate,
                'gst_amount' => $gst_amount,
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