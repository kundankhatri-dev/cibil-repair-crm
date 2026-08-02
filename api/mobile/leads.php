<?php
require_once '../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function verifyToken($conn) {
    $headers = getallheaders();
    $token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
    $stmt = mysqli_prepare($conn, "SELECT user_id FROM mobile_tokens WHERE token = ? AND expires_at > NOW()");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($method === 'GET') {
    $token_data = verifyToken($conn);
    if (!$token_data) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    $query = "SELECT id, customer_name, customer_phone, service, status, created_at, commission_amount FROM partner_leads ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    $leads = mysqli_fetch_all($result, MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'leads' => $leads]);
}

elseif ($method === 'POST') {
    $token_data = verifyToken($conn);
    if (!$token_data) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
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

elseif ($method === 'PUT') {
    $token_data = verifyToken($conn);
    if (!$token_data) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $stmt = mysqli_prepare($conn, "UPDATE partner_leads SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $input['status'], $input['id']);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update']);
    }
}

mysqli_close($conn);
?>