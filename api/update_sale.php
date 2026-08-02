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

$id = isset($input['id']) ? intval($input['id']) : 0;
$customer_name = isset($input['customer_name']) ? trim($input['customer_name']) : '';
$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$service = isset($input['service']) ? trim($input['service']) : '';
$status = isset($input['status']) ? trim($input['status']) : '';
$sale_date = isset($input['sale_date']) ? trim($input['sale_date']) : date('Y-m-d');
$notes = isset($input['notes']) ? trim($input['notes']) : '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Sale ID required']);
    exit;
}

if (empty($customer_name) || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Customer name and amount required']);
    exit;
}

$check = mysqli_query($conn, "SELECT id FROM sales WHERE id = $id");
if (mysqli_num_rows($check) == 0) {
    echo json_encode(['success' => false, 'error' => 'Sale not found']);
    exit;
}

$sql = "UPDATE sales SET 
        customer_name = '$customer_name',
        amount = $amount,
        service = '$service',
        status = '$status',
        sale_date = '$sale_date',
        notes = '$notes'
        WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    $result = mysqli_query($conn, "SELECT * FROM sales WHERE id = $id");
    $sale = mysqli_fetch_assoc($result);
    echo json_encode([
        'success' => true,
        'message' => 'Sale updated',
        'sale' => $sale
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>