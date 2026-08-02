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

$email = isset($input['email']) ? trim($input['email']) : '';
$name = isset($input['name']) ? trim($input['name']) : '';
$role = isset($input['role']) ? trim($input['role']) : 'client';

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email required']);
    exit;
}

$check = mysqli_query($conn, "SELECT id, name, email, role, status FROM users WHERE email = '$email'");
$user = mysqli_fetch_assoc($check);

if (!$user) {
    $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (name, email, password, role, status, created_at) VALUES ('$name', '$email', '$password', '$role', 'active', NOW())");
    $user_id = mysqli_insert_id($conn);
    $user = ['id' => $user_id, 'name' => $name, 'email' => $email, 'role' => $role, 'status' => 'active'];
}

session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];

echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => $user]);

mysqli_close($conn);
exit;
?>