<?php
// ============================================================
// CIBIL REPAIR CRM - Get Registration Code
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$db_host = 'localhost';
$db_name = 'u929623538_cibil';
$db_user = 'u929623538_cibilrepair';
$db_pass = 'Kundanlaxmi@1995';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

if (!$id && empty($code)) {
    echo json_encode(['success' => false, 'error' => 'ID or Code required']);
    exit;
}

$tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'registration_codes'");
if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
    echo json_encode(['success' => false, 'error' => 'Table not found']);
    exit;
}

if ($id > 0) {
    $sql = "SELECT * FROM registration_codes WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    $sql = "SELECT * FROM registration_codes WHERE code = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Registration code not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => $data
]);

mysqli_close($conn);
?>