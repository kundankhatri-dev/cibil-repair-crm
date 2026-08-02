<?php
require_once '../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Generate JWT token
function generateToken($user_id, $email, $role) {
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'user_id' => $user_id,
        'email' => $email,
        'role' => $role,
        'exp' => time() + (7 * 24 * 60 * 60) // 7 days
    ]));
    $signature = hash_hmac('sha256', "$header.$payload", 'YOUR_SECRET_KEY');
    return "$header.$payload.$signature";
}

function verifyToken($token) {
    $parts = explode('.', $token);
    if (count($parts) != 3) return false;
    
    $payload = json_decode(base64_decode($parts[1]), true);
    if ($payload['exp'] < time()) return false;
    
    return $payload;
}

if ($method === 'POST' && $action === 'login') {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $role = trim($input['role'] ?? '');
    
    $query = "SELECT id, name, email, phone, role, status, password FROM users WHERE email = ? AND role = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $email, $role);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user && password_verify($password, $user['password'])) {
        $token = generateToken($user['id'], $user['email'], $user['role']);
        unset($user['password']);
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => $user,
            'expires_in' => 604800
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
    }
}

elseif ($method === 'GET' && $action === 'dashboard') {
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    $payload = verifyToken($token);
    
    if (!$payload) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    
    $user_id = $payload['user_id'];
    
    $stats = [];
    $stats['total_customers'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'client'"))['count'];
    $stats['total_leads'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM partner_leads"))['count'];
    $stats['total_revenue'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as total FROM quotations"))['total'];
    
    echo json_encode(['success' => true, 'stats' => $stats]);
}

elseif ($method === 'GET' && $action === 'leads') {
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    $payload = verifyToken($token);
    
    if (!$payload) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $query = "SELECT id, customer_name, customer_phone, service, status, created_at FROM partner_leads ORDER BY id DESC LIMIT $limit";
    $result = mysqli_query($conn, $query);
    $leads = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'leads' => $leads]);
}

elseif ($method === 'POST' && $action === 'lead') {
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    $payload = verifyToken($token);
    
    if (!$payload) {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $stmt = mysqli_prepare($conn, "INSERT INTO partner_leads (customer_name, customer_phone, service, source) VALUES (?, ?, ?, 'Mobile App')");
    mysqli_stmt_bind_param($stmt, "sss", $input['name'], $input['phone'], $input['service']);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'id' => mysqli_insert_id($conn)]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add lead']);
    }
}

mysqli_close($conn);
?>