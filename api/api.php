<?php
// ============================================================
// CIBIL REPAIR CRM - Unified API Endpoint
// ============================================================

// ===== DISABLE ERROR DISPLAY =====
ini_set('display_errors', 0);
error_reporting(0);

// ===== SET HEADERS =====
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
header('X-Content-Type-Options: nosniff');

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ===== INCLUDE DATABASE =====
require_once __DIR__ . '/db.php';

// ===== CHECK CONNECTION =====
if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// ============================================================
// GET ACTION
// ============================================================

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ============================================================
// CLIENTS ACTIONS
// ============================================================

// Get all clients
if ($action === 'getClients') {
    $query = "SELECT id, name, email, phone, role, status, created_at FROM users WHERE role = 'client' ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    $clients = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $clients[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $clients]);
    exit;
}

// Add new client
elseif ($action === 'addClient') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $name = mysqli_real_escape_string($conn, $data['name'] ?? '');
    $email = mysqli_real_escape_string($conn, $data['email'] ?? '');
    $phone = mysqli_real_escape_string($conn, $data['phone'] ?? '');
    
    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Name and email are required']);
        exit;
    }
    
    // Check if email exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'error' => 'Email already exists']);
        exit;
    }
    
    $query = "INSERT INTO users (name, email, phone, role, status, created_at) 
              VALUES ('$name', '$email', '$phone', 'client', 'approved', NOW())";
    
    if (mysqli_query($conn, $query)) {
        $id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'message' => 'Client added successfully', 'data' => ['id' => $id]]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// Update client
elseif ($action === 'updateClient') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $id = (int)$data['id'];
    $name = mysqli_real_escape_string($conn, $data['name'] ?? '');
    $email = mysqli_real_escape_string($conn, $data['email'] ?? '');
    $phone = mysqli_real_escape_string($conn, $data['phone'] ?? '');
    $status = mysqli_real_escape_string($conn, $data['status'] ?? 'approved');
    
    if (!$id || empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'error' => 'ID, name and email are required']);
        exit;
    }
    
    $query = "UPDATE users SET name = '$name', email = '$email', phone = '$phone', status = '$status' WHERE id = $id AND role = 'client'";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Client updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// Delete client
elseif ($action === 'deleteClient') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $id = (int)$data['id'];
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Client ID is required']);
        exit;
    }
    
    $query = "DELETE FROM users WHERE id = $id AND role = 'client'";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Client deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// ============================================================
// CASES ACTIONS
// ============================================================

// Get all cases
elseif ($action === 'getCases') {
    $query = "SELECT c.*, u.name as client_name, u.email as client_email 
              FROM cases c 
              LEFT JOIN users u ON c.client_id = u.id 
              ORDER BY c.id DESC";
    $result = mysqli_query($conn, $query);
    $cases = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cases[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $cases]);
    exit;
}

// Add new case
elseif ($action === 'addCase') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $client_id = (int)$data['client_id'];
    $service = mysqli_real_escape_string($conn, $data['service'] ?? '');
    $amount = (float)$data['amount'];
    $case_no = 'CASE' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $status = mysqli_real_escape_string($conn, $data['status'] ?? 'pending');
    
    if (!$client_id || empty($service) || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Client ID, service, and amount are required']);
        exit;
    }
    
    $query = "INSERT INTO cases (case_no, client_id, service, amount, status, created_at) 
              VALUES ('$case_no', $client_id, '$service', $amount, '$status', NOW())";
    
    if (mysqli_query($conn, $query)) {
        $id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'message' => 'Case added successfully', 'data' => ['id' => $id, 'case_no' => $case_no]]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// Update case status
elseif ($action === 'updateCaseStatus') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $id = (int)$data['id'];
    $status = mysqli_real_escape_string($conn, $data['status'] ?? 'pending');
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Case ID is required']);
        exit;
    }
    
    $allowedStatus = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($status, $allowedStatus)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status. Allowed: ' . implode(', ', $allowedStatus)]);
        exit;
    }
    
    $query = "UPDATE cases SET status = '$status' WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Case status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// ============================================================
// LEADS ACTIONS
// ============================================================

// Get all leads
elseif ($action === 'getLeads') {
    $query = "SELECT * FROM leads ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    $leads = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $leads[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $leads]);
    exit;
}

// Add new lead
elseif ($action === 'addLead') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $name = mysqli_real_escape_string($conn, $data['name'] ?? '');
    $phone = mysqli_real_escape_string($conn, $data['phone'] ?? '');
    $email = mysqli_real_escape_string($conn, $data['email'] ?? '');
    $service = mysqli_real_escape_string($conn, $data['service'] ?? '');
    $source = mysqli_real_escape_string($conn, $data['source'] ?? 'website');
    $status = mysqli_real_escape_string($conn, $data['status'] ?? 'new');
    
    if (empty($name) || empty($phone)) {
        echo json_encode(['success' => false, 'error' => 'Name and phone are required']);
        exit;
    }
    
    $query = "INSERT INTO leads (name, phone, email, service, source, status, created_at) 
              VALUES ('$name', '$phone', '$email', '$service', '$source', '$status', NOW())";
    
    if (mysqli_query($conn, $query)) {
        $id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'message' => 'Lead added successfully', 'data' => ['id' => $id]]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// Update lead status
elseif ($action === 'updateLeadStatus') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $id = (int)$data['id'];
    $status = mysqli_real_escape_string($conn, $data['status'] ?? 'new');
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Lead ID is required']);
        exit;
    }
    
    $query = "UPDATE leads SET status = '$status' WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true, 'message' => 'Lead status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// ============================================================
// PAYMENTS ACTIONS
// ============================================================

// Get all payments
elseif ($action === 'getPayments') {
    $query = "SELECT p.*, u.name as client_name, u.email as client_email 
              FROM payments p 
              LEFT JOIN users u ON p.client_id = u.id 
              ORDER BY p.id DESC";
    $result = mysqli_query($conn, $query);
    $payments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $payments[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $payments]);
    exit;
}

// Add payment
elseif ($action === 'addPayment') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $client_id = (int)$data['client_id'];
    $amount = (float)$data['amount'];
    $payment_method = mysqli_real_escape_string($conn, $data['payment_method'] ?? 'cash');
    $status = mysqli_real_escape_string($conn, $data['status'] ?? 'completed');
    
    if (!$client_id || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Client ID and valid amount are required']);
        exit;
    }
    
    $query = "INSERT INTO payments (client_id, amount, payment_method, status, created_at) 
              VALUES ($client_id, $amount, '$payment_method', '$status', NOW())";
    
    if (mysqli_query($conn, $query)) {
        $id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'message' => 'Payment added successfully', 'data' => ['id' => $id]]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// ============================================================
// DEFAULT RESPONSE
// ============================================================

else {
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid action',
        'available_actions' => [
            'getClients', 'addClient', 'updateClient', 'deleteClient',
            'getCases', 'addCase', 'updateCaseStatus',
            'getLeads', 'addLead', 'updateLeadStatus',
            'getPayments', 'addPayment'
        ]
    ]);
    exit;
}

// ============================================================
// CLOSE CONNECTION
// ============================================================

mysqli_close($conn);
?>