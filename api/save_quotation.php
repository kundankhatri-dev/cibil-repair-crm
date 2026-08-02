<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$customer = isset($input['customer']) ? trim($input['customer']) : '';
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$service = isset($input['service']) ? trim($input['service']) : 'CIBIL Repair';

if (empty($customer) || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Customer and amount required']);
    exit;
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_no VARCHAR(50) UNIQUE,
    customer VARCHAR(255),
    amount DECIMAL(10,2),
    gst_amount DECIMAL(10,2) DEFAULT 0,
    total_with_gst DECIMAL(10,2) DEFAULT 0,
    service VARCHAR(255),
    status VARCHAR(50) DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM quotations"))['count'] ?? 0;
$quote_no = 'QUO' . date('Y') . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
$gst_amount = $amount * 0.18;
$total_with_gst = $amount + $gst_amount;

$sql = "INSERT INTO quotations (quote_no, customer, amount, gst_amount, total_with_gst, service) 
        VALUES ('$quote_no', '$customer', $amount, $gst_amount, $total_with_gst, '$service')";

if (mysqli_query($conn, $sql)) {
    $id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => 'Quotation created',
        'id' => $id,
        'quote_no' => $quote_no,
        'amount' => $amount,
        'gst_amount' => $gst_amount,
        'total_with_gst' => $total_with_gst
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>