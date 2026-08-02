<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Customer ID required']);
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM customers WHERE id = $id");
$customer = mysqli_fetch_assoc($result);

if (!$customer) {
    echo json_encode(['success' => false, 'error' => 'Customer not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => $customer
]);

mysqli_close($conn);
exit;
?>