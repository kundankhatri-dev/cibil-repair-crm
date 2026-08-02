<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    http_response_code(500);
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
$description = isset($input['description']) ? trim($input['description']) : '';
$price = isset($input['price']) ? floatval($input['price']) : 0;
$category = isset($input['category']) ? trim($input['category']) : 'other';
$duration = isset($input['duration']) ? trim($input['duration']) : '30-45 days';
$icon = isset($input['icon']) ? trim($input['icon']) : '⭐';
$status = isset($input['status']) ? trim($input['status']) : 'active';
$is_featured = isset($input['is_featured']) ? intval($input['is_featured']) : 0;
$is_popular = isset($input['is_popular']) ? intval($input['is_popular']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Service ID required']);
    exit;
}

if (empty($name) || empty($description) || $price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Name, description and price required']);
    exit;
}

$check = mysqli_query($conn, "SELECT id FROM services WHERE id = $id");
if (mysqli_num_rows($check) == 0) {
    echo json_encode(['success' => false, 'error' => 'Service not found']);
    exit;
}

$sql = "UPDATE services SET 
        name = '$name',
        description = '$description',
        price = $price,
        category = '$category',
        duration = '$duration',
        icon = '$icon',
        status = '$status',
        is_featured = $is_featured,
        is_popular = $is_popular
        WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    $result = mysqli_query($conn, "SELECT * FROM services WHERE id = $id");
    $service = mysqli_fetch_assoc($result);
    echo json_encode([
        'success' => true,
        'message' => 'Service updated',
        'service' => $service
    ]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
exit;
?>