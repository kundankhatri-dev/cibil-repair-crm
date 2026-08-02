<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$conn = mysqli_connect('localhost', 'u929623538_cibilrepair', 'Kundanlaxmi@1995', 'u929623538_cibil');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$code = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $code = isset($_GET['code']) ? trim($_GET['code']) : '';
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = isset($input['code']) ? trim($input['code']) : '';
}

if (empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Registration code required']);
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM registration_codes WHERE code = '$code'");

if (mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid registration code']);
    exit;
}

$row = mysqli_fetch_assoc($result);

if ($row['is_used'] == 1) {
    echo json_encode(['success' => false, 'error' => 'Code already used']);
    exit;
}

if (strtotime($row['expires_at']) < time()) {
    echo json_encode(['success' => false, 'error' => 'Code expired']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Code is valid',
    'data' => [
        'code' => $row['code'],
        'role' => $row['role'] ?? 'client',
        'expires_at' => $row['expires_at'],
        'is_valid' => true
    ]
]);

mysqli_close($conn);
exit;
?>