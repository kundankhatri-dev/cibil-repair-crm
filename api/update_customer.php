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
$name = isset($input['name']) ? trim($input['name']) : '';
$city = isset($input['city']) ? trim($input['city']) : '';
$status = isset($input['status']) ? trim($input['status']) : 'active';

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Customer ID required']);
    exit;
}

$check = mysqli_query($conn, "SELECT id FROM customers WHERE id = $id");
if (mysqli_num_rows($check) == 0) {
    echo json_encode(['success' => false, 'error' => 'Customer not found']);
    exit;
}

$updates = [];
if (!empty($name)) $updates[] = "name = '$name'";
if (!empty($city)) $updates[] = "city = '$city'";
if (!empty($status)) $updates[] = "status = '$status'";

if (empty($updates)) {
    echo json_encode(['success' => false, 'error' => 'No fields to update']);
    exit;
}

$sql = "UPDATE customers SET " . implode(', ', $updates) . " WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    $result = mysqli_query($conn, "SELECT * FROM customers WHERE id = $id");
    echo json_encode([
        'success' => true,
        'message' => 'Customer updated',
        'customer' => mysqli_fetch_assoc($result)
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>