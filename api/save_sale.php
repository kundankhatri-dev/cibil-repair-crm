<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$customer_name = isset($input['customer_name']) ? trim($input['customer_name']) : '';
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$service = isset($input['service']) ? trim($input['service']) : 'CIBIL Repair';
$customer_email = isset($input['customer_email']) ? trim($input['customer_email']) : '';
$customer_phone = isset($input['customer_phone']) ? trim($input['customer_phone']) : '';
$status = isset($input['status']) ? trim($input['status']) : 'Completed';
$payment_method = isset($input['payment_method']) ? trim($input['payment_method']) : 'Cash';
$date = isset($input['date']) ? trim($input['date']) : date('Y-m-d');

if (empty($customer_name) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Customer name and amount required']);
    exit;
}

// Calculate GST
$gst_amount = $amount * 0.18;
$total_with_gst = $amount + $gst_amount;

// Generate invoice
$count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM sales"))['count'] ?? 0;
$invoice_no = 'INV-' . date('Y') . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

$sql = "INSERT INTO sales (customer_name, customer_email, customer_phone, service, amount, gst_amount, total_with_gst, invoice_no, payment_method, status, sale_date) 
        VALUES ('$customer_name', '$customer_email', '$customer_phone', '$service', $amount, $gst_amount, $total_with_gst, '$invoice_no', '$payment_method', '$status', '$date')";

if (mysqli_query($conn, $sql)) {
    $id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Sale created',
        'id' => $id,
        'invoice_no' => $invoice_no,
        'amount' => $amount,
        'gst_amount' => $gst_amount,
        'total_with_gst' => $total_with_gst
    ]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>